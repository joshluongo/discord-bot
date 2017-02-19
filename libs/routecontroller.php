<?php

class RouteController {
    protected $container;

    // constructor receives container instance
    public function __construct(Slim\Container $container) {
        $this->container = $container;
    }

    public function messageParser($request, $response, $args) {
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
            // Use the magic of filters.
            $processedInput = $textProcessor->performFilters($filters, $processedInput);
        }

        // Send it!
        $worked = DiscordHook::callDiscordHook($request->getAttribute('channel'), $request->getAttribute('key'), $processedInput);

        return $worked ? $response->withStatus(200)->getBody()->write("ok") : $response->withStatus(400);
    }

    public function slackParser($request, $response, $args) {
        // Text Processor
        $textProcessor = new TextProcessor();

        // Tag someone?
        $target = $textProcessor->boolval_real($request->getQueryParam("target", null));

        // Message 
        $parsedBody = "";

        // Get content type.
        $contentType = $request->getContentType();

        // Skip filters.
        $skipFilters = [];

        // Decode
        if ($contentType == "application/json") {
            // Get the raw input.
            $dataObj = $request->getParsedBody();

            // Process String.
            $parsedBody = $textProcessor->processSlackObject($dataObj);

            // Remove `html` filter as it has alredy been run.
            $skipFilters[] = "html";
        } else if ($contentType == "application/x-www-form-urlencoded") {
            // Get the raw input.
            $dataObj = json_decode($request->getParsedBody()["payload"], true);

            // Process String.
            $parsedBody = $textProcessor->processSlackObject($dataObj);

            // Remove `html` filter as it has alredy been run.
            $skipFilters[] = "html";
        } else {
            // Standard Message
            $parsedBody = urldecode($request->getBody());
        }

        // Process the input
        $processedInput = $textProcessor->sanitizeInput($parsedBody, $target);

        // Get filters.
        $filters = $request->getQueryParam("filter", null);

        // Use filters if needed.
        if (!is_null($filters)) {
            // Use the magic of filters.
            $processedInput = $textProcessor->performFilters($filters, $processedInput, $skipFilters);
        }

        // Send it!
        $worked = DiscordHook::callDiscordHook($request->getAttribute('channel'), $request->getAttribute('key'), $processedInput);

        return $worked ? $response->withStatus(200)->getBody()->write("ok") : $response->withStatus(400);

        return $response;
    }
}

?>