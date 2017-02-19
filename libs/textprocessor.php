<?php
/**
*  processor.php
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

class TextProcessor {

  /**
   * Converts a string to a boolean.
   * Source: http://php.net/manual/en/function.boolval.php#116547
   *
   * @param $input String
   *
   * @return boolean
  **/
  public function boolval_real($val, $return_null=false) {
      $boolval = ( is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $val );
      return ( $boolval===null && !$return_null ? false : $boolval );
  }

  /**
   * Santizes input for discord. With the option to add a targeted message.
   *
   * @param $input String
   * @param $target String|null
   *
   * @return string
  **/
  public function sanitizeInput($input, $target=null) {
    // We need to patch the input due a PHP nbsp issue.
    return (empty($target) ? "" : "@$target") . $this->stripNotifyCommands($this->expandNewLines(str_replace("\xc2\xa0", ' ', $input)));
  }

  /**
   * Strips the notification commands for discord.
   * Supported: @everyone, @here.
   *
   * @param $input String
   *
   * @return string
  **/
  protected function stripNotifyCommands($input) {
    return str_ireplace("@everyone", "at everyone", str_ireplace("@here", "at here", $input));
  }

  /**
   * Expand new line markers.
   *
   * @param $input String
   *
   * @return string
  **/
  protected function expandNewLines($input) {
    return str_ireplace('\n', "\n", $input);
  }

  /**
   * Convert Slack object to string.
   *
   * @param $input array
   *
   * @return string
  **/
  public function processSlackObject($input) {
    // Output container.
    $output = [];

    // Contains text?
    if (isset($input["text"])) {
      $output[] = $input["text"];
    }
    
    // Check for attachments
    if (isset($input["attachments"]) && is_array($input["attachments"])) {
      // Loop over attachments
      foreach ($input["attachments"] as $attachment) {
        if (!empty($attachment["title"]) && !empty($attachment["title_link"])) {
          // Title + Link
          $output[] = $attachment["title"] . " " . $attachment["title_link"];
        } else if (!empty($attachment["title"])) {
          // Just a message
          $output[] = $attachment["title"];
        } else if (!empty($attachment["fallback"])) {
          // Just a fallback
          $output[] = $attachment["fallback"];
        }
      }
    }

    // Decode links.
    foreach ($output as $messageKey => $message) {
      // Regex
      preg_match_all("/<(.*?)\|(.*?)>/", $message, $links_array);

      // Check if we found links.
      if (count($links_array[0]) == 0) {
        continue;
      }

      // Merge
      foreach($links_array[0] as $key => $value) {
        // Build new string.
        $newlink = "(" . $links_array[2][$key] . " - " . $links_array[1][$key] . ")";

        // Find and replace on string.
        $message = str_ireplace($value, $newlink, $message);
      }

      // Replace
      $output[$messageKey] = $message;
    }

    return \Html2Text\Html2Text::convert(implode("\n", $output));
  }

  /**
   * Perform filters on input.
   *
   * Built In Filter Types:
   * [html] - Converts HTML to plain text.
   * [whitespace] - Makes everything all whitespace single space.
   *
   * @param $filters String. This should be a CSV.
   * @param $input String
   * @param $skipFilters Array
   *
   * @return string
  **/
  public function performFilters($filters, $input, $skipFilters=[]) {
    $newString = $input;

    // Split filters.
    $filters = explode(",", preg_replace('/\s+/', '', $filters));

    // Only unique filters.
    if (!is_array($filters)) {
      return $newString;
    }

    $filters = array_unique($filters);

    // Expand filter set by the env.
    $customFilters = $this->getEnvFilters();

    // Loop over data and apply actions.
    foreach ($filters as $key => $value) {
      $name = strtolower(trim($value));

      switch ($name) {

        case 'html':
          if (!in_array('html', $skipFilters)) {
            $newString = \Html2Text\Html2Text::convert($newString);
          }
          break;

        case 'whitespace':
          if (!in_array('whitespace', $skipFilters)) {
            $newString = preg_replace('/\h+/', ' ', $newString);
          }
          break;

        default:
          if (isset($customFilters[$name])) {
            // Found it!
            $find = $customFilters[$name][0];
            $repl = $customFilters[$name][1];

            // Check data.
            if ($find[0] == "\\") {
              // Regex.
              $newString = preg_replace($find, $repl, $newString);
            } else {
              // Plain.
              $newString = $this->replaceKeepCase($find, $repl, $newString);
            }
          }
          break;
      }
    }

    return $newString;
  }

  /**
   * Parses filters from the env.
   * Returned as an assoc array.
   * Format: {{<KEY>}}={{<FIND>}}={{<REPLACE>}}~{{<KEY>}}={{<FIND>}}={{<REPLACE>}}
   *
   * @return array
  **/
  protected function getEnvFilters() {
    $customFiltersRaw = getenv("CUSTOM_FILTERS");
    $customFilters = [];

    // Check it.
    if ($customFiltersRaw === false) {
      // No custom filters.
      return [];
    }

    // Split it.
    $customFilterOut = explode("~", $customFiltersRaw);

    // Loop.
    foreach ($customFilterOut as $key => $value) {
      // Split the value.
      $inner = explode('=', $value);

      // Check length.
      if (count($inner) != 3) {
        // Bad filter.
        continue;
      }

      // Found it!
      $key  = preg_replace('/^{{/', '', preg_replace('/}}$/', '', $inner[0]));
      $find = preg_replace('/^{{/', '', preg_replace('/}}$/', '', $inner[1]));
      $repl = preg_replace('/^{{/', '', preg_replace('/}}$/', '', $inner[2]));

      // Add it to the filters.
      $customFilters[strtolower(trim($key))] = [$find, $repl];
    }

    print_r($customFilters);

    return $customFilters;
  }

  /**
   * Case insentive find and replace but keep case.
   * Source: http://stackoverflow.com/a/16740335/1246419
   *
   * @param $search String.
   * @param $replace String
   * @param $subject String
   *
   * @return string
   **/
  protected function replaceKeepCase($search, $replace, $subject) {
    $uppercase_search = strtoupper($search);
    $titleCase_search = ucwords($search);

    $lowercase_replace = strtolower($replace);
    $uppercase_replace = strtoupper($replace);
    $titleCase_replace = ucwords($replace);

    $subject = str_replace($uppercase_search, $uppercase_replace, $subject);
    $subject = str_replace($titleCase_search, $titleCase_replace, $subject);
    $subject = str_ireplace($search, $lowercase_replace, $subject);

    return $subject;
  }
}

?>
