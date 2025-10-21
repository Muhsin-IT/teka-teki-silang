
<?php
// api.php?action=getState
// api.php?action=setState (POST: state JSON)

header('Content-Type: application/json');
$stateFile = __DIR__ . '/state.json';
action:
$action = isset($_GET['action']) ? $_GET['action'] : 'getState';

if($action === 'getState'){
    if(!file_exists($stateFile)){
        $default = [
            'current'=>null, // nomor soal aktif
            'revealed'=>[],  // nomor yang sudah di-reveal
            'answers'=>[],   // revealed answers text mapping
            'teams'=>[ 'Tim A'=>0, 'Tim B'=>0, 'Tim C'=>0, 'Tim D'=>0 ],
            'lastUpdate'=>time()
        ];
        file_put_contents($stateFile, json_encode($default));
        echo json_encode($default);
        exit;
    }
    echo file_get_contents($stateFile);
    exit;
}

if($action === 'setState'){
    $input = file_get_contents('php://input');
    if(!$input){
        echo json_encode(['ok'=>false,'msg'=>'no input']); exit;
    }
    $data = json_decode($input, true);
    if(!$data){ echo json_encode(['ok'=>false,'msg'=>'invalid json']); exit; }

    // read existing
    $state = [];
    if(file_exists($stateFile)) $state = json_decode(file_get_contents($stateFile), true);
    if(!$state) $state = [];

    // merge allowed keys
    foreach(['current','revealed','answers','teams'] as $k){
        if(isset($data[$k])) $state[$k] = $data[$k];
    }
    $state['lastUpdate'] = time();
    file_put_contents($stateFile, json_encode($state));
    echo json_encode(['ok'=>true,'state'=>$state]);
    exit;
}

echo json_encode(['ok'=>false,'msg'=>'unknown action']);
?>
