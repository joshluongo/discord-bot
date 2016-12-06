<?php
/**
*  discordhook.php
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

class DiscordHook {

  /**
   * Posts a message to a discord webhook.
   *
   * @param $channel number
   * @param $key String
   * @param $message String
   *
   * @return boolean
  **/
  public static function callDiscordHook($channel, $key, $message) {
    // Create a client with a base URI
    $client = new GuzzleHttp\Client(['base_uri' => 'https://discordapp.com/api/webhooks/']);

    // Send a request to Discord.
    $response = $client->request('POST', "$channel/$key", [
        'form_params' => [
            'content' => $message,
        ]
    ]);

    // Check the code.
    $code = $response->getStatusCode();

    return ($code >= 200 && $code < 300) || $code == 304;
  }

}

?>
