<?php

(PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) && die('This script is for CLI mode only');

$babelExtensions = [
    "NJUPTAAA/NOJ_Extension_Codeforces",
    "NJUPTAAA/NOJ_Extension_ContestHunter",
    "NJUPTAAA/NOJ_Extension_POJ",
    "NJUPTAAA/NOJ_Extension_Vijos",
    "NJUPTAAA/NOJ_Extension_PTA",
    "NJUPTAAA/NOJ_Extension_UVa",
    "NJUPTAAA/NOJ_Extension_HDU",
    "NJUPTAAA/NOJ_Extension_UVaLive",
    "NJUPTAAA/NOJ_Extension_ZOJ",
    "NJUPTAAA/NOJ_Extension_AtCoder",
    "NJUPTAAA/NOJ_Extension_BZOJ",
    "NJUPTAAA/NOJ_Extension_NOIOPEN",
];

$packages = [[
    "name" => "NOJ",
    "code" => "noj",
    "type" => "online-judge",
    "description" => "Official Online Judge Interface for NOJ",
    "license" => "MIT",
    "repository" => "https://github.com/ZsgsDesign/NOJ",
    "downloadURL" => null,
    "version" => "0.5.0",
    "website" => "https://acm.njupt.edu.cn/",
    "require" => [
        "NOJ" => "0.5.0"
    ],
    "official" => true,
    "icon" => "resources/noj.png",
    "maintainers" => [
        "ZsgsDesign",
        "X3ZvaWQ",
        "pikanglong",
        "DavidDiao",
        "ChenKS12138",
        "Rp12138",
        "goufaan",
        "Brethland",
        "scrutinizer-auto-fixer",
        "fossabot",
        "YoujieZhang",
        "crazyasme",
        "SinonJZH"
    ]
]];

foreach ($babelExtensions as $repo) {
    $repoURL = "https://github.com/$repo";

    $versionInfos = json_decode(getGitHubTags("https://api.github.com/repos/$repo/tags"), true);
    echo "Processing: $repo" . PHP_EOL;
    if (isset($versionInfos["url"])) {
        $versionInfos = json_decode(getGitHubTags($versionInfos["url"]), true);
    }
    $downloadURL=[];
    $latestVersion=null;
    foreach ($versionInfos as $versionInfo) {
        $version = $versionInfo["name"];
        echo "Processing: $repo @ $version" . PHP_EOL;
        $remoteBabelInfo = json_decode(getRemoteBabelConfig($repo, $version), true);
        if (empty($remoteBabelInfo)) {
            echo("Failure: Babel Config Not Found, Skipping $repo @ $version") . PHP_EOL;
        }
        if ($remoteBabelInfo["version"] != $version) {
            echo("Version Mismatch, Skipping $repo @ $version") . PHP_EOL;
        }
        $downloadURL[] = [
            "version" => $version,
            "url" => "https://github.com/$repo/archive/$version.zip"
        ];
        if (is_null($latestVersion)) {
            $latestVersion=[
                "name" => $remoteBabelInfo['name'],
                "code" => $remoteBabelInfo['code'],
                "type" => $remoteBabelInfo['type'],
                "description" => $remoteBabelInfo['description'],
                "website" => $remoteBabelInfo['website'],
                "license" => $remoteBabelInfo['license'],
                "version" => $version
            ];
            if (isset($remoteBabelInfo['require'])) {
                if (isset($remoteBabelInfo['require']['NOJ'])) {
                    $latestVersion['require']['NOJ']=$remoteBabelInfo['require']['NOJ'];
                }
                if (isset($remoteBabelInfo['require']['tlsv1.3'])) {
                    $latestVersion['require']['tlsv1.3']=$remoteBabelInfo['require']['tlsv1.3'];
                }
            } else {
                $latestVersion['require']=null;
            }
            $contributorInfos = json_decode(getGitHubTags("https://api.github.com/repos/$repo/contributors"), true);
            foreach ($contributorInfos as $contributor) {
                $latestVersion['maintainers'][]=$contributor['login'];
            }
        }
    }

    $packages[] = [
        "name" => $latestVersion["name"],
        "code" => $latestVersion["code"],
        "type" => $latestVersion["type"],
        "description" => $latestVersion["description"],
        "license" => $latestVersion["license"],
        "repository" => $repoURL,
        "downloadURL" => $downloadURL,
        "version" => $latestVersion["version"],
        "website" => $latestVersion["website"],
        "require" => $latestVersion['require'],
        "official" => true,
        "icon" => "resources/".$latestVersion["code"].".png",
        "maintainers" => $latestVersion['maintainers']
    ];
}

$babelConfig = [
    "_readme" => [
        "This file records all known Babel Extensions approved by NOJ Official Babel Marketspace",
        "Read more about it at https://acm.njupt.edu.cn/babel and https://njuptaaa.github.io/babel",
        "This file is updated on a daily basis"
    ],
    "updated-at" => time(),
    "content-hash" => md5(json_encode($packages)),
    "packages" => $packages
];

file_put_contents("babel.json", json_encode($babelConfig, JSON_PRETTY_PRINT));

function getGitHubTags($repo)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $repo);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.github.v3+json',
        'User-Agent: request',
    ]);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    return $result;
}

function getRemoteBabelConfig($repo, $version){
    return _getRemoteBabelConfig($repo, $version);
}

function _getRemoteBabelConfig($repo, $version, $tries=5){
    if(!$tries) return false;
    $ret = @file_get_contents("https://ghproxy.com/https://raw.githubusercontent.com/$repo/$version/babel.json");
    if($ret===false){
        return _getRemoteBabelConfig($repo, $version, $tries-1);
    }
    return $ret;
}