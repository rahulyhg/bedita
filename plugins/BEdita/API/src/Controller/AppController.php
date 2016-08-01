<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */
namespace BEdita\API\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotAcceptableException;
use Cake\Network\Exception\NotFoundException;
use Cake\Routing\Router;

/**
 * Base class for all API Controller endpoints.
 *
 * @since 4.0.0
 *
 * @property \BEdita\API\Controller\Component\JsonApiComponent $JsonApi
 */
class AppController extends Controller
{
    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        parent::initialize();

        if (!$this->apiKeyCheck()) {
            throw new ForbiddenException('No valid API KEY found');
        }

        $this->loadComponent('BEdita/API.Paginator');

        $this->loadComponent('RequestHandler');
        if ($this->request->is(['json', 'jsonapi'])) {
            $this->loadComponent('BEdita/API.JsonApi', [
                'contentType' => $this->request->is('json') ? 'json' : null,
                'checkMediaType' => $this->request->is('jsonapi'),
            ]);

            $this->RequestHandler->config('inputTypeMap.json', [[$this->JsonApi, 'parseInput']], false);
            $this->RequestHandler->config('viewClassMap.json', 'BEdita/API.JsonApi');
        }

        $this->corsSettings();

        if (empty(Router::fullBaseUrl())) {
            Router::fullBaseUrl(
                rtrim(
                    sprintf('%s://%s/%s', $this->request->scheme(), $this->request->host(), $this->request->base),
                    '/'
                )
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function beforeFilter(Event $event)
    {
        if ((Configure::read('debug') || Configure::read('Accept.html')) && $this->request->is('html')) {
            return $this->html();
        } elseif (!$this->request->is(['json', 'jsonapi'])) {
            throw new NotAcceptableException('Bad request content type "' . implode('" "', $this->request->accepts()) . '"');
        }

        return null;
    }

    /**
     * Check API KEY from request header.
     * API KEYS are stored in configuration with this structure:
     *
     *  'ApiKeys' => [
     *    'sdgwr89081023jfdklewRASdasdwdfswdr' => [
     *      'label' => 'web app', // (optional)
     *      'origin' => 'example.com', // (optional) could be '*'
     *    ],
     *    'w4nvwpq5028DDfwnrK2933293423nfnaa4' => [
     *       ....
     *    ],
     *
     * Check rules are:
     *   - if no Api Keys are defined -> request is always accepted
     *   - if one or more Api Keys are defined
     *      - current X-Api-Key header value should be one of these keys
     *      - if corresponding Key has an 'origin' request origin should match
     *      - otherwise an error response is sent - HTTP 403
     *
     * @return bool True if check is passed, false otherwise
     */
    protected function apiKeyCheck()
    {
        $apiKeys = Configure::read('ApiKeys');
        if (!empty($apiKeys)) {
            $requestKey = $this->request->header('X-Api-Key');
            if (!$requestKey || !isset($apiKeys[$requestKey])) {
                return false;
            }
            $key = $apiKeys[$requestKey];
            if (!empty($key['origin']) && $key['origin'] !== '*' &&
                $key['origin'] !== $this->request->header('Origin')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Setup CORS from configuration
     * An optional 'CORS' key in should be like this example:
     *
     * 'CORS' => [
     *   'allowOrigin' => '*.example.com',
     *   'allowMethods' => ['GET', 'POST'],
     *   'allowHeaders' => ['X-CSRF-Token']
     * ]
     *
     * where:
     *   - 'allowOrigin' is a single domain or an array of domains
     *   - 'allowMethods' is an array of HTTP methods
     *   - 'allowHeaders' is an array of HTTP headers
     *
     *
     * @return void
     */
    protected function corsSettings()
    {
        $corsConfig = Configure::read('CORS');
        if (!empty($corsConfig)) {
            $corsBuilder = $this->response->cors($this->request);
            $corsAllowed = ['allowOrigin' => '', 'allowMethods' => '', 'allowHeaders' => ''];
            $corsAccepted = array_intersect_key($corsConfig, $corsAllowed);
            foreach ($corsAccepted as $corsOption => $corsValue) {
                $corsBuilder->{$corsOption}($corsValue);
            }
            $corsBuilder->build();
        }
    }


    /**
     * Action to display HTML layout.
     *
     * @return \Cake\Network\Response
     * @throws \Cake\Network\Exception\NotFoundException
     */
    protected function html()
    {
        if ($this->request->is('requested')) {
            throw new NotFoundException();
        }

        $method = $this->request->method();
        $url = Router::reverse($this->request);
        $response = $this->requestAction($url, [
            'environment' => [
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'REQUEST_METHOD' => $method,
            ],
        ]);

        $this->set(compact('method', 'response', 'url'));

        $this->viewBuilder()->template('Common/html');

        return $this->render();
    }
}