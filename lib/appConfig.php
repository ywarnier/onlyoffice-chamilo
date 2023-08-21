<?php
/**
 *
 * (c) Copyright Ascensio System SIA 2023
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

require_once __DIR__ . "/../../../main/inc/global.inc.php";

class AppConfig {

    /**
     * The config key for the jwt header
     *
     * @var string
     */
    private const jwtHeader = "onlyoffice_jwt_header";

    /**
     * Get the jwt header setting
     *
     * @return string
     */
    public static function JwtHeader()
    {
        $header = api_get_configuration_value(self::jwtHeader);
        if (empty($header)) {
            $header = "Authorization";
        }

        return $header;
    }

    /**
     * DEMO DATA
     */
    private const DEMO_PARAM = [
        "ADDR" => "https://onlinedocs.onlyoffice.com/",
        "HEADER" => "AuthorizationJWT",
        "SECRET" => "sn2puSUF7muF5Jas",
        "TRIAL" => 30
    ];

    /**
     * Get demo params
     *
     * @return array
     */
    public static function GetDemoParams()
    {
        return self::DEMO_PARAM;
    }
}
