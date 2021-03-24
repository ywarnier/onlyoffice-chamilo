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

require_once __DIR__.'/../../main/inc/global.inc.php';

const USER_AGENT_MOBILE = "/android|avantgo|playbook|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\\/|plucker|pocket|psp|symbian|treo|up\\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i";

$plugin = OnlyofficePlugin::create();

$isEnable = $plugin->get("enableOnlyofficePlugin") === 'true';
if (!$isEnable) {
    die ("Document server is't enable");
    return;
}

$documentServerUrl = $plugin->get("documentServerUrl");
if (empty($documentServerUrl)) {
    die ("Document server is't configured");
    return;
}

$config = [];

$docApiUrl = $documentServerUrl . "/web-apps/apps/api/documents/api.js";

$docId = $_GET["docId"];
$groupId = isset($_GET["groupId"]) && !empty($_GET["groupId"]) ? $_GET["groupId"] : null;

$userId = api_get_user_id();

$userInfo = api_get_user_info($userId);

$sessionId = api_get_session_id();
$courseId = api_get_course_int_id();
$courseInfo = api_get_course_info();
$courseCode = $courseInfo["code"];

$docInfo = DocumentManager::get_document_data_by_id($docId, $courseCode, false, $sessionId);

$extension = strtolower(pathinfo($docInfo["title"], PATHINFO_EXTENSION));

$langInfo = LangManager::getLangUser();

$docType = FileUtility::getDocType($extension);
$key = FileUtility::getKey($courseCode, $docId);
$fileUrl = FileUtility::getFileUrl($courseId, $userId, $docId, $sessionId, $groupId);

$config = [
    "type" => "desktop",
    "documentType" => $docType,
    "document" => [
        "fileType" => $extension,
        "key" => $key,
        "title" => $docInfo["title"],
        "url" => $fileUrl
    ],
    "editorConfig" => [
        "lang" => $langInfo["isocode"],
        "region" => $langInfo["isocode"],
        "user" => [
            "id" => strval($userId),
            "name" => $userInfo["username"]
        ],
        "customization" => [
            "goback" => [
                "blank" => false,
                "requestClose" => false,
                "text" => get_lang("Back"),
                "url" => $_SERVER["HTTP_REFERER"]
            ],
            "compactHeader" => true,
            "toolbarNoTabs" => true
        ]
    ]
];

$userAgent = $_SERVER['HTTP_USER_AGENT'];

$isMobileAgent = preg_match(USER_AGENT_MOBILE, $userAgent);
if ($isMobileAgent) {
    $config['type'] = 'mobile';
}

$isAllowToEdit = api_is_allowed_to_edit(true, true);
$isMyDir = DocumentManager::is_my_shared_folder($userId, $docInfo["absolute_parent_path"], $sessionId);

$isGroupAccess = false;
if (!empty($groupId)) {
    $groupProperties = GroupManager::get_group_properties($groupId);
    $docInfoGroup = api_get_item_property_info(api_get_course_int_id(), 'document', $docId, $sessionId);
    $isGroupAccess = GroupManager::allowUploadEditDocument($userId, $courseCode, $groupProperties, $docInfoGroup);

    $isMemberGroup = GroupManager::is_user_in_group($userId, $groupProperties);

    if (!$isGroupAccess) {
        if (!$groupProperties["status"]) {
            api_not_allowed(true);
        }
        if (!$isMemberGroup && $groupProperties["doc_state"] != 1) {
            api_not_allowed(true);
        }
    }
}

$accessRights = $isAllowToEdit || $isMyDir || $isGroupAccess ? true : false;
$canEdit = in_array($extension, FileUtility::$can_edit_types) ? true : false;

$isVisible = DocumentManager::check_visibility_tree($docId, $courseInfo, $sessionId, $userId, $groupId);
$isReadonly = $docInfo["readonly"];

if (!$isVisible) {
    api_not_allowed(true);
}

if ($canEdit && $accessRights && !$isReadonly) {
    $config["editorConfig"]["mode"] = "edit";
    $config["editorConfig"]["callbackUrl"] = getCallbackUrl($docId, $userId, $courseId, $sessionId, $groupId);
} else {
    $canView = in_array($extension, FileUtility::$can_view_types) ? true : false;
    if ($canView) {
        $config["editorConfig"]["mode"] = "view";
    } else {
        api_not_allowed(true);
    }
}
$config["document"]["permissions"]["edit"] = $accessRights && !$isReadonly;

/**
 * Return callback url
 * 
 * @param int $docId - identifier of document
 * @param int $userId - identifier of user
 * @param int $courseId - identifier of course
 * @param int $sessionId - identifier of session
 * @param int $groupId - identifier of group or null if file out of group
 * 
 * @return string
 */
function getCallbackUrl($docId, $userId, $courseId, $sessionId, $groupId) {
    $url = "";

    $data = [
        "type" => "track",
        "courseId" => $courseId,
        "userId" => $userId,
        "docId" => $docId,
        "sessionId" => $sessionId
    ];

    if (!empty($groupId)) {
        $data["groupId"] = $groupId;
    }

    $hashUrl = Crypt::GetHash($data);

    $url = $url . api_get_path(WEB_PLUGIN_PATH) . "onlyoffice/callback.php?hash=" . $hashUrl;

    return $url;
}

?>
<title>ONLYOFFICE</title>
<style>
    #app > iframe {
        height: calc(100% - 140px);
    }
    body {
        height: 100%;
    }
    .chatboxheadmain,
    .pull-right,
    .breadcrumb {
        display: none;
    }
</style>
<script type="text/javascript" src=<?php echo $docApiUrl?>></script>
<script type="text/javascript">
    var onAppReady = function () {
        innerAlert("Document editor ready");
    };
    var connectEditor = function () {
        $("#cm-content")[0].remove(".container");
        $("#main").append('<div id="app-onlyoffice">' +
                            '<div id="app">' +
                                '<div id="iframeEditor">' +
                                '</div>' +
                            '</div>' +
                          '</div>');

        var config = <?php echo json_encode($config)?>;
        var isMobileAgent = <?php echo json_encode($isMobileAgent)?>;

        config.events = {
            "onAppReady": onAppReady
        };

        docEditor = new DocsAPI.DocEditor("iframeEditor", config);

        $(".navbar").css({"margin-bottom": "0px"});
        $("body").css({"margin": "0 0 0px"});
        if (isMobileAgent) {
            var frameEditor = $("#app > iframe")[0];
            $(frameEditor).css({"height": "100%", "top": "0px"});
        }
    }

    if (window.addEventListener) {
        window.addEventListener("load", connectEditor);
    } else if (window.attachEvent) {
        window.attachEvent("load", connectEditor);
    }

</script>
<?php echo Display::display_header(); ?>