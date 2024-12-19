<?php

declare(strict_types=1);

namespace OCA\AccountExport\Controller;

use OCA\AccountExport\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OC\User\Manager as UserManager;
use OCP\AppFramework\Services\IInitialState;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;

/**
 * @psalm-suppress UnusedClass
 */
class PageController extends Controller
{
	public function __construct(
		string $appName,
		IRequest $request,
		private UserManager $userManager,
		private IGroupManager $groupManager,
		private IUserSession $userSession,
		private IInitialState $initialState,
		private IFactory $l10nFactory,
	) {
		parent::__construct($appName, $request);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function index(): TemplateResponse
	{
		$user = $this->userSession->getUser();
		$uid = $user->getUID();
		$isAdmin = $this->groupManager->isAdmin($uid);
		$isDelegatedAdmin = $this->groupManager->isDelegatedAdmin($uid);
		$sortGroupsBy = 1;

		$groupsInfo = new \OC\Group\MetaData(
			$uid,
			$isAdmin,
			$isDelegatedAdmin,
			$this->groupManager,
			$this->userSession
		);

		$groupsInfo->setSorting($sortGroupsBy);
		[$adminGroup, $groups] = $groupsInfo->get();

		$disabledUsers = -1;
		$userCount = 0;

		if ($isAdmin || $isDelegatedAdmin) {
			$disabledUsers = $this->userManager->countDisabledUsers();
			$userCount = array_reduce($this->userManager->countUsers(), function ($v, $w) {
				return $v + (int)$w;
			}, 0);
		} else {
			// User is subadmin !
			// Map group list to ids to retrieve the countDisabledUsersOfGroups
			$userGroups = $this->groupManager->getUserGroups($user);
			$groupsIds = [];

			foreach ($groups as $key => $group) {
				// $userCount += (int)$group['usercount'];
				$groupsIds[] = $group['id'];
			}

			$userCount += $this->userManager->countUsersOfGroups($groupsInfo->getGroups());
			$disabledUsers = $this->userManager->countDisabledUsersOfGroups($groupsIds);
		}

		$userCount -= $disabledUsers;


		$recentUsersGroup = [
			'id' => '__nc_internal_recent',
			'name' => 'Recently active',
			'usercount' => $this->userManager->countSeenUsers(),
		];

		$disabledUsersGroup = [
			'id' => 'disabled',
			'name' => 'Disabled accounts',
			'usercount' => $disabledUsers
		];

		$languages = $this->l10nFactory->getLanguages();

		$serverData = [];
		$serverData['groups'] = array_merge_recursive($adminGroup, [$recentUsersGroup, $disabledUsersGroup], $groups);
		$serverData['userCount'] = $userCount;
		$serverData['isAdmin'] = $isAdmin;
		$serverData['isDelegatedAdmin'] = $isDelegatedAdmin;
		$serverData['userCount'] = $userCount;
		$serverData['languages'] = $languages;

		$this->initialState->provideInitialState('accountExportUsersSettings', $serverData);

		return new TemplateResponse(
			Application::APP_ID,
			'index',
		);
	}
}
