<?php
/**
*  index.php
*  JR Apps Discord Bot.
*
*  Copyright 2016 Josh Luongo (JR Apps).
*
*  Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
*
*  http://www.apache.org/licenses/LICENSE-2.0
*
*  Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
*/

// Import Composer.
require '../vendor/autoload.php';

// Slim.
$c = new \Slim\Container();
$c['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        return $c['response']
            ->withStatus(500);
    };
};
$c['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $c['response']
            ->withStatus(404);
    };
};
$c['notAllowedHandler'] = function ($c) {
    return function ($request, $response, $methods) use ($c) {
        return $c['response']
            ->withStatus(405);
    };
};
$app = new \Slim\App($c);

// Discord Webhook Helper.
$app->post('/post/{channel}/{key}', function ($request, $response, $args) {
    // Text Processor
    $textProcessor = new TextProcessor();

    // Tag someone?
    $target = $textProcessor->boolval_real($request->getQueryParam("target", null));

    // Get the raw input.
    $parsedBody = urldecode($request->getBody());

    // Process the input
    $processedInput = $textProcessor->sanitizeInput($parsedBody, $target);

    // Get filters.
    $filters = $request->getQueryParam("filter", null);

    // Use filters if needed.
    if (!is_null($filters)) {
      // Get content type.
      $contentType = $request->getHeader('Content-Type');

      // Use the magic of filters.
      $processedInput = $textProcessor->performFilters($filters, $processedInput, (count($contentType)>0?$contentType[0]:null));
    }

    // Send it!
    $worked = DiscordHook::callDiscordHook($request->getAttribute('channel'), $request->getAttribute('key'), $processedInput);

    return $worked ? $response->withStatus(204) : $response->withStatus(400);
});

$app->run();

?>
