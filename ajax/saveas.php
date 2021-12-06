<?php
/**
 *
 * (c) Copyright Ascensio System SIA 2021
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

require_once __DIR__.'/../../../main/inc/global.inc.php';

use ChamiloSession as Session;

$userId = api_get_user_id();

$title = $_POST["title"];
$url = $_POST["url"];

$folderId = !empty($_POST["folderId"]) ? $_POST["folderId"] : 0;
$sessionId = !empty($_POST["sessionId"]) ? $_POST["sessionId"] : 0;
$courseId = !empty($_POST["courseId"]) ? $_POST["courseId"] : 0;
$groupId = !empty($_POST["groupId"]) ? $_POST["groupId"] : 0;

$courseInfo = api_get_course_info_by_id($courseId);
$courseCode = $courseInfo["code"];

$isMyDir = false;
if (!empty($folderId)) {
    $folderInfo = DocumentManager::get_document_data_by_id(
        $folderId,
        $courseCode,
        true,
        $sessionId
    );
    $isMyDir = DocumentManager::is_my_shared_folder(
        $userId,
        $folderInfo["absolute_path"],
        $sessionId
    );
}
$groupRights = Session::read('group_member_with_upload_rights');
$isAllowToEdit = api_is_allowed_to_edit(true, true);
if (!($isAllowToEdit || $isMyDir || $groupRights)) {
    echo json_encode(["error" => "Not permitted"]);
    return;
}

$fileExt = strtolower(pathinfo($title, PATHINFO_EXTENSION));
$baseName = strtolower(pathinfo($title, PATHINFO_FILENAME));

$result = FileUtility::createFile(
    $baseName,
    $fileExt,
    $folderId,
    $userId,
    $sessionId,
    $courseId,
    $groupId,
    $url
);

if (isset($result["error"])) {
    echo json_encode($result);
    return;
}

echo json_encode(["success" => "File is created"]);