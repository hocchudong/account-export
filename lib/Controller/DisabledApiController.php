<?php

declare(strict_types=1);

namespace OCA\AccountExport\Controller;

use Exception;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OC\Authentication\Token\RemoteWipe;
use OC\KnownUser\KnownUserService;
use OC\SubAdmin;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\ISubAdmin;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IPhoneNumberUtil;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;

use function Sabre\Uri\split;

/**
 * AllApiController for account export
 */
class DisabledApiController extends AUserExportData
{
	private $defaultHeader = [
		"no" => "No",
		"displayName" => "Display Name",
		"accountName" => "Account Name",
		"password" => "Password",
		"email" => "Email",
		"groups" => "Groups",
		"groupAdminFor" => "Group admin for",
		"quota" => "Quota",
		"manager" => "Manager",
		"language" => "Language",
		"accountBackend" => "Account Backend",
		"lastLogin" => "Last login"
	];
	private IL10N $l10n;

	public function __construct(
		string $appName,
		IRequest $request,
		IUserManager $userManager,
		IConfig $config,
		IGroupManager $groupManager,
		IUserSession $userSession,
		IAccountManager $accountManager,
		ISubAdmin $subAdminManager,
		IFactory $l10nFactory,
		private LoggerInterface $logger,
	) {
		parent::__construct(
			$appName,
			$request,
			$userManager,
			$config,
			$groupManager,
			$userSession,
			$accountManager,
			$subAdminManager,
			$l10nFactory
		);

		$this->l10n = $l10nFactory->get($appName);
	}

	/**
	 * API download file export account
	 *
	 * @return DataResponse<Http::STATUS_OK, array{message: string}, array{}>
	 *
	 * 200: Data returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/disabled/download-export')]
	public function getAllAccountsAPI(string $displayFields)
	{
		$user = $this->userSession->getUser();


		// Admin? Or SubAdmin?
		$uid = $user->getUID();
		$subAdminManager = $this->groupManager->getSubAdmin();
		$isAdmin = $this->groupManager->isAdmin($uid);
		$isDelegatedAdmin = $this->groupManager->isDelegatedAdmin($uid);


		// write header
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		$sheet->getStyle('A:A')->getAlignment()->setHorizontal('center');
		$sheet->getStyle('A1:Z1')->getFont()->setBold(true);

		$listDisplayFieldRequest = explode(",", $displayFields);
		$listHeaderName = [];
		$listItemDisplay = [];

		foreach ($this->defaultHeader as $key => $value) {
			if (in_array($key, $listDisplayFieldRequest)) {
				array_push($listHeaderName, $value);
				array_push($listItemDisplay, $key);
			}
		}

		$this->writeExcelHeader($sheet, $listItemDisplay, $listHeaderName);

		$offset = 0;
		$list_users_result = [];

		$rowExcelIndex = 2;
		do {
			$users = $this->getListUserDisabled($user, $isAdmin, $isDelegatedAdmin, $subAdminManager, $offset);

			$list_users_result = array_merge($list_users_result, $users);

			$listUserDetail = [];
			foreach ($users as $userId) {

				$userId = (string)$userId;
				try {
					$userData = $this->getUserData($userId);
				} catch (Exception) {
					$this->logger->warning('Find user error');
				}
				if ($userData !== null) {
					$listUserDetail[$userId] = $userData;
					$this->writeRowData($sheet, $userData, $listItemDisplay, $rowExcelIndex);

					$rowExcelIndex += 1;
				} else {
					$listUserDetail[$userId] = ['id' => $userId];
				}
			}
			$offset += 25;
		} while (count($users) > 0);

		$writer = new Xlsx($spreadsheet);

		$tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';

		$writer->save($tempFile);

		$datetimeFormat = 'Y-m-d_H:i:s';
		$now = new \DateTime();

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="export_disabled_acounts' . $now->format($datetimeFormat) . '.xlsx"');
		header('Content-Length: ' . filesize($tempFile));
		readfile($tempFile);

		unlink($tempFile);
		exit;
	}

	private function getListUserDisabled(IUser $currentUser, bool $isAdmin, bool $isDelegatedAdmin, SubAdmin $subAdminManager, int $offset)
	{
		$users = [];
		if ($isAdmin || $isDelegatedAdmin) {
			$users = $this->userManager->getDisabledUsers(25, $offset, "");
			$users = array_map(fn (IUser $user): string => $user->getUID(), $users);
		} elseif ($subAdminManager->isSubAdmin($currentUser)) {
			$subAdminOfGroups = $subAdminManager->getSubAdminsGroups($currentUser);

			$users = [];
			$tempLimit = (25 === null ? null : 25 + $offset);
			foreach ($subAdminOfGroups as $group) {
				$users = array_unique(array_merge(
					$users,
					array_map(
						fn (IUser $user): string => $user->getUID(),
						array_filter(
							$group->searchUsers(""),
							fn (IUser $user): bool => !$user->isEnabled()
						)
					)
				));
				if (($tempLimit !== null) && (count($users) >= $tempLimit)) {
					break;
				}
			}
			$users = array_slice($users, $offset, 25);
		}

		return $users;
	}
}
