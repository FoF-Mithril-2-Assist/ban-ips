<?php

/*
 * This file is part of fof/ban-ips.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\BanIPs\Middleware;

use Flarum\Api\JsonApiResponse;
use Flarum\User\UserRepository;
use FoF\BanIPs\Repositories\BannedIPRepository;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Exception\Handler\ResponseBag;
use Zend\Diactoros\Response\RedirectResponse;

class RegisterMiddleware implements MiddlewareInterface
{
    /**
     * @var BannedIPRepository
     */
    private $bannedIPs;

    /**
     * @var UserRepository
     */
    private $users;

    /**
     * @param BannedIPRepository $bannedIPs
     * @param UserRepository     $users
     */
    public function __construct(BannedIPRepository $bannedIPs, UserRepository $users)
    {
        $this->bannedIPs = $bannedIPs;
        $this->users = $users;
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $registerUri = app('flarum.forum.routes')->getPath('register');
        $loginUri = app('flarum.forum.routes')->getPath('login');
        $logoutUri = app('flarum.forum.routes')->getPath('logout');
        $actor = $request->getAttribute('actor');
        $requestUri = $request->getUri()->getPath();

        if ($requestUri === $registerUri || $requestUri === $loginUri) {
            $ipAddress = array_get($request->getServerParams(), 'REMOTE_ADDR', '127.0.0.1');
            $bannedIP = $ipAddress != null ? $this->bannedIPs->findByIPAddress($ipAddress) : null;

            if ($bannedIP != null && $bannedIP->deleted_at == null) {
                if ($requestUri === $loginUri && $identification = Arr::get($request->getParsedBody(), 'identification')) {
                    $user = $this->users->findByIdentification($identification);

                    if ($user != null && !$this->bannedIPs->isUserBanned($user)) {
                        return $handler->handle($request);
                    }
                }

                $error = new ResponseBag('422', [
                    [
                        'status' => '422',
                        'code'   => 'validation_error',
                        'source' => [
                            'pointer' => '/data/attributes/ip',
                        ],
                        'detail' => app('translator')->trans('fof-ban-ips.error.banned_ip_message'),
                    ],
                ]);

                $document = new Document();
                $document->setErrors($error->getErrors());

                return new JsonApiResponse($document, $error->getStatus());
            }
        }

        if (!$actor->isGuest() && $requestUri !== $logoutUri && $this->bannedIPs->isUserBanned($actor)) {
            $token = $request->getAttribute('session')->token();

            return new RedirectResponse($logoutUri.'?token='.$token);
        }

        return $handler->handle($request);
    }
}
