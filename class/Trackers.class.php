<?php
$dir = dirname(__FILE__).'/../';

// ��������� ����������� ������� engine
foreach (glob($dir."trackers/*.engine.php") as $trackerEngine) {
    include_once $trackerEngine;
    
    $tracker = str_replace('.engine.php', '', basename($trackerEngine));
    Trackers::addTracker($tracker, 'engine');
}
    
// ��������� ����������� ������� search
foreach (glob($dir."trackers/*.search.php") as $trackerEngine) {
    include_once $trackerEngine;

    $tracker = str_replace('.search.php', '', basename($trackerEngine));
    Trackers::addTracker($tracker, 'search');
}

class Trackers {
    
    public static $trackersList    = array(); // ������ ������ ���������� � ���� ������������ ���������
    public static $associationList = array(); // ������ ������ ��� ������ �������� � ��������� �����
    
    // ������� ���������� ����� ��������, ��� � ��� �������� �������� � ����������
    // ���������:
    //    tracker   - ��� ��������, ��� �������� ���������� �������� �����
    //    classType - ��� ������������� ������. ���������� �������� 'engine' � 'search'
    // ���������:
    //    ������������ ����� ��� ��������.
    private static function getClassName($tracker, $classType = '')
    {
        // �� ���� ������ ���������� ������� �����
        $suffix = '';
        if ($classType == 'search')
            $suffix = 'Search';
        
        // ��������������� ��� �������� � ��� ������
        $className = mb_strtolower( str_replace(array('.', '-'), '', $tracker) ).$suffix;
        
        if  (!class_exists($className))
            return null;
        
        return $className;
    }
    
    // ��������� ��������� ���������� ������ ����������
    // ���������:
    //    tracker - ��� ��������
    //    classType - ��� ������������� ������. ���������� �������� 'engine' � 'search'
    public static function addTracker($tracker, $classType = '') {
        
        $trackerClass = Trackers::getClassName($tracker);
        
        $trackerType = Trackers::getTrackerType($tracker, $classType);
        // ��������� ������� � ������
        Trackers::$trackersList[] = array(
                                        'tracker' => $tracker,
                                        'type' => $trackerType,
                                    );
        
        // ��������� ���������� ��� � �����
        Trackers::$associationList[$tracker] = $tracker;
        
        //���� � ������ ���� ����� 'getAssociations', �� �������� ������ ��������� ���� � ����������� � ������
        if ( method_exists($trackerClass, 'getAssociations') ) {
            $associations = $trackerClass::getAssociations();
            foreach($associations as $association)
                Trackers::$associationList[$association] = $tracker;
        }
    }
    
    // ������� ��������� �������� �� ������� ������ ��� ��������
    // ���������:
    //    tracker - ��� ��������
    // ���������:
    //    ������������ ����� ��� ��������.
    public static function moduleExist($tracker, $classType = '') {
        
        $trackerClass = Trackers::getClassName($tracker, $classType);
        
        return ($trackerClass != null);
    }
    
    // ������� ���������� ��� ��������
    // ���������:
    //    tracker - ��� ��������
    // ���������:
    //    ���������� ��� ��������.
    public static function getTrackerType($tracker, $classType = '') {
        
        $trackerClass = Trackers::getClassName($tracker, $classType);
        
        if ($trackerClass != null && method_exists($trackerClass, 'getTrackerType'))
            return $trackerClass::getTrackerType();
        else if ($trackerClass != null && $classType == 'search')
            return 'search';
        else if ($trackerClass != null)
            return 'threme';
        else
            return null;
    }
    
    // ������� ��������� �������� ������� �� ����������
    // ���������:
    //    classType   - ��� ������ ��� �������� ������� ��������. ���������� �������� 'engine' � 'search'
    //    tracker     - ��� ��������
    //    torrentInfo - ������ � ����������� �������
    // ���������:
    //    ������������ ��� ���������� ���������� ��������.
    public static function checkUpdate($tracker, $torrentInfo, $classType) {
        
        $trackerClass = Trackers::getClassName($tracker, $classType);
        
       if ($trackerClass == null){ // ����� �� ������
            return null;
        }
        else {
            if($classType == 'engine')
                $trackerClass::main($torrentInfo);
            else if ($classType == 'search')
                $trackerClass::mainSearch($torrentInfo);
        }
    }
    
    // ������� ��������� ��������� URL ������ ��� ��������
    // ���������:
    //    tracker     - ��� ��������
    //    torrent_id  - ������������� ������� �� ��������
    // ���������:
    //    ������������ ������� ������ �� �������
    public static function generateURL($tracker, $torrent_id) {
        
        $trackerClass = Trackers::getClassName($tracker);
        
        $torrent_url = "";
        if ( $trackerClass != null && method_exists($trackerClass, 'generateURL') )
            $torrent_url = $trackerClass::generateURL($tracker, $torrent_id);
        
        return $torrent_url;
    }
    
    // ������� �������� �������� URL`�
    public static function checkRule($tracker, $data) {
        
        $trackerClass = Trackers::getClassName($tracker);
        
        return $trackerClass::checkRule($data);
    }
    
    // ������� ���������� ��� �������� � ������ ���� ��������� ����������
    // ���������:
    //    tracker - ��� ��������
    public static function getTrackerName($tracker) {
        
        if( isset(Trackers::$associationList[$tracker]) && ! empty(Trackers::$associationList[$tracker]) )
            $tracker = Trackers::$associationList[$tracker];
        
        return $tracker;
    }
    
    // ������� �������� �� ������ ������������� �������
    // ���������:
    //    tracker - ��� ��������
    //    url - ������ �� �������
    // ���������:
    //    ������������ ������������� �������
    public static function getThreme($tracker, $url) {
        
        $trackerClass = Trackers::getClassName($tracker);
        
        if( method_exists($trackerClass, 'getThreme') )
            $threme = $trackerClass::getThreme($url);
        else{
            $url = parse_url($url);
            $query = explode('=', $url['query']);
            $threme = $query[1];
        }
        
        return $threme;
    }
    
    // ������� �������� ����� ���������, ������� ������������� ����������� ����
    // ���������:
    //    trackerType - ��� ������� ���������
    // ���������:
    //    ������ ���������� ��������� ���������
    public static function getTrackersByType($trackerType) {
        $result = array();
        foreach (Trackers::$trackersList as $trackerData) {
            if ($trackerData['type'] == $trackerType)
                $result[] = $trackerData;
        }
        return $result;
    }
}
?>