<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Middleware;

use OCA\Passwords\Controller\Api\ServiceApiController;
use OCA\Passwords\Controller\Api\SessionApiController;
use OCA\Passwords\Controller\Api\SettingsApiController;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\User\UserChallengeHelper;
use OCA\Passwords\Services\SessionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\ISession;

/**
 * Class ApiSessionMiddleware
 *
 * @package OCA\Passwords\Middleware
 */
class ApiSessionMiddleware extends Middleware {

    /**
     * @var ISession
     */
    protected $session;

    /**
     * @var UserChallengeHelper
     */
    protected $passwordHelper;

    /**
     * @var SessionService
     */
    protected $sessionService;

    /**
     * ApiSessionMiddleware constructor.
     *
     * @param SessionService      $sessionService
     * @param UserChallengeHelper $passwordHelper
     * @param ISession            $session
     */
    public function __construct(SessionService $sessionService, UserChallengeHelper $passwordHelper, ISession $session) {
        $this->sessionService = $sessionService;
        $this->session        = $session;
        $this->passwordHelper = $passwordHelper;
    }

    /**
     * @param Controller $controller
     * @param string     $methodName
     *
     * @throws ApiException
     */
    public function beforeController($controller, $methodName): void {
        if(!$this->isApiRequest($controller)) return;

        $this->sessionService->load();
        $id = $this->sessionService->getId();
        if($id != $this->session->get(SessionService::API_SESSION_KEY)) {
            $this->session->set(SessionService::API_SESSION_KEY, $id);
        }

        if(!$this->sessionService->isAuthorized() && $this->requiresAuthorization($controller, $methodName)) {
            throw new ApiException('Session required', Http::STATUS_PRECONDITION_FAILED);
        }

        parent::beforeController($controller, $methodName);
    }

    /**
     * @param Controller $controller
     * @param string     $methodName
     * @param Response   $response
     *
     * @return Response
     */
    public function afterController($controller, $methodName, Response $response): Response {
        if(!$this->isApiRequest($controller)) return $response;

        $this->sessionService->save();
        $response->addHeader(SessionService::API_SESSION_HEADER, $this->sessionService->getId());

        return parent::afterController($controller, $methodName, $response);
    }

    /**
     * @param Controller $controller
     *
     * @return bool
     */
    protected function isApiRequest(Controller $controller): bool {
        $class = get_class($controller);

        return substr($class, 0, 28) == 'OCA\Passwords\Controller\Api' && strpos($class, '\\Legacy\\') === false;
    }

    /**
     * @param Controller $controller
     * @param string     $method
     *
     * @return bool
     */
    protected function requiresAuthorization(Controller $controller, string $method): bool {

        if(!$this->passwordHelper->hasChallenge()) {
            return false;
        }

        if($controller instanceof SessionApiController && in_array($method, ['open', 'request', 'requestToken'])) {
            return false;
        }

        if($controller instanceof ServiceApiController && in_array($method, ['getAvatar', 'getFavicon', 'getPreview'])) {
            return false;
        }

        if($controller instanceof SettingsApiController && in_array($method, ['get', 'list'])) {
            return false;
        }

        return true;
    }
}