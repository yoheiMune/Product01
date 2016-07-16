<?php

include '../include/func.php';
include '../include/message.php';

/**
 * 開催情報リストを返す
 *
 * @param mode: 1:表示地図内リスト取得、2:全件リスト取得
 * @param withinOneWeekFlag: 0:すべて、1:一週間以内
 * @param startingPointLatitudo: 表示地図の左上始点の緯度
 * @param startingPointLongitude: 表示地図の左上始点の経度
 * @param endPointLatitudo: 表示地図の右下終点の緯度
 * @param endPointLongitude: 表示地図の右下終点の経度
 * @return hostInfoList
 *             holdingDateYmd
 *             hostList
 *                 hostGroupId
 *                 hostName
 *                 placeName
 *                 holdingSchedule
 *                 latitudo
 *                 longitude
 *                 eventName
 *                 branchScale
 *
 * @author nishijima
 **/
$mode = (string)filter_input(INPUT_GET, $_REQUEST['mode']);
$weekFlag = (string)filter_input(INPUT_GET, $_REQUEST['withinOneWeekFlag']);
$strLati = (double)filter_input(INPUT_GET, $_REQUEST['startingPointLatitudo']);
$strLong = (double)filter_input(INPUT_GET, $_REQUEST['startingPointLongitude']);
$endLati = (double)filter_input(INPUT_GET, $_REQUEST['endPointLatitudo']);
$endLong = (double)filter_input(INPUT_GET, $_REQUEST['endPointLongitude']);

try {
    // 必須チェック
    if($mode || $weekFlag) {
        throw new RuntimeException($msg_hostlistApi_parameter_error001);
    }

    // 表示地図内リスト取得の場合は、緯度経度情報がすべてセットされていることをチェック
    if($mode === '1' && !(empty($strLati) || empty($strLong) || empty($endLati) || empty($endLong))) {
        throw new RuntimeException($msg_hostlistApi_parameter_error002);
    }
 
    $pdo = createDbo();

    if($mode === '1') {
        // 表示地図内開催情報取得
        if($weekFlag === "0") {
            // 開催日制限なし
            $stmt = $pdo->prepare($getHostListScopeMap);

            $stmt->bindValue(':strLati', $strLati, PDO::PARAM_STR);
            $stmt->bindValue(':strLong', $strLati, PDO::PARAM_STR);
            $stmt->bindValue(':endLati', $strLati, PDO::PARAM_STR);
            $stmt->bindValue(':endLong', $strLati, PDO::PARAM_STR);
            $stmt->execute();
        } else if($weekFlag === "1") {
            // 1週間以内に開催
            $stmt = $pdo->prepare($getHostListScopwMapOneWeek);

            $stmt->bindValue(':date_ymd', getDateYmdAfterOneWeek());
            $stmt->bindValue(':strLati', $strLati, PDO::PARAM_STR);
            $stmt->bindValue(':strLong', $strLati, PDO::PARAM_STR);
            $stmt->bindValue(':endLati', $strLati, PDO::PARAM_STR);
            $stmt->bindValue(':endLong', $strLati, PDO::PARAM_STR);
            $stmt->execute();
        }
    } else if($mode === "2") {
        // 全件取得
        if($weekFlag === "0") {
            // 開催日制限なし
            $stmt = $pdo->prepare($getHostList);

            $stmt->execute();
        } else if($weekFlag === "1") {
            // 1週間以内に開催
            $stmt = $pdo->prepare($getHostListOneWeek);

            $stmt->bindValue(':date_ymd', getDateYmdAfterOneWeek());
            $stmt->execute();
        }
    }

    $hostInfoList = array();
    $hostList = array();
    $tmpHoldingDateYmd = NULL;

    foreach($stmt->(PDO::FETCH_ASSOC as $row)) {

        if(!is_null($tmpHoldingDateYmd)) {
            $tmpHoldingDateYmd = $row['holding_date_ymd'];
        } else if($tmpHoldingDateYmd !== $row['holding_date_ymd']) {
            $hostInfoList[] = array(
                'holdingDateYmd'=>$tmpHoldingDateYmd
                'hostList'=>$hostList[]
            );
            $hostList = array();
            $tmpHoldingDateYmd = $row['holding_date_ymd'];
        }

        $host = array();
        $host[] = array(
            'hostGroupId'=>$row['host_group_id']
            'hostName'=>$row['host_name']
            'placeName'=>$row['place_name']
            'holdingSchedule'=>$row['holding_schedule']
            'latitudo'=>$row['latitudo']
            'longitude'=>$row['longitude']
//            イベント情報は一旦後回し
//            'eventName'=>$row['host_group_id']
            'branchScale'=>$row['branch_scale']
        );

        $hostList[] = $host;
    }

    $hostInfoList[] = array(
        'holdingDateYmd'=>$tmpHoldingDateYmd
        'hostList'=>$hostList[]
    );

    returnJson($hostInfoList);
} catch(RuntimeException $e) {
    header($msg_http_404_error001);
    exit(0);
} catch(Exception $e) {
    header($msg_http_500_error001);
    exit(0);
}

?>