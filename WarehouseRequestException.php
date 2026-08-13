<?php

/**
 * @file
 * Exception class for validation errors in Indicia forms.
 *
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/client_helpers
 */

namespace IForm;

/**
 * Exception class for warehouse request errors in Indicia forms.
 */
final class WarehouseRequestException extends \RuntimeException {

  /**
   * Constructor for WarehouseRequestException.
   *
   * @param int $httpStatus
   *   HTTP status code of the response, or 0 if no response was received.
   * @param int $curlErrno
   *   cURL error number, or 0 if no cURL error occurred.
   * @param string $curlError
   *   cURL error message, or empty string if no cURL error occurred.
   * @param string $responseBody
   *   Response body from the warehouse, or empty string if no response was
   *   received.
   */
  public function __construct(
    public readonly int $httpStatus,
    public readonly int $curlErrno,
    public readonly string $curlError,
    public readonly string $responseBody = '',
  ) {
    $message = NULL;
    if ($this->responseBody) {
      $decoded = json_decode($this->responseBody, TRUE);
      if (is_array($decoded) && isset($decoded['msg'])) {
        $message = $decoded['msg'];
      }
    }
    if (!$message) {
      $message = $curlError ?: "Warehouse request failed with HTTP {$httpStatus}.";
    }

    parent::__construct(
      $message,
      $httpStatus ?: $curlErrno,
    );
  }

  /**
   * Determines if this exception represents a warehouse unavailable error.
   *
   * @return bool
   *   True when this is a 503 response or host is unavailable, otherwise false.
   */
  public function isUnavailable(): bool {
    // Treat 502 Bad Gateway, 503 Service Unavailable, and 504 Gateway Timeout
    // as warehouse unavailable errors.
    return in_array($this->httpStatus, [502, 503, 504], TRUE)
      || in_array($this->curlErrno, [
        CURLE_COULDNT_RESOLVE_HOST,
        CURLE_COULDNT_CONNECT,
        CURLE_OPERATION_TIMEDOUT,
      ], TRUE);
  }

}
