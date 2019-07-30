<?php

// exec('sh ./webhook.sh');

// $shell = 'sh ./webhook.sh';
// $shell = 'git remote -v';

// system($shell, $status);
// system($shell, $status);

exec("git pull 2>&1",$out);
var_export($out);

//注意shell命令的执行结果和执行返回的状态值的对应关系
// $shell = "<font color='red'>$shell</font>";
// if( $status ){
//     echo "shell命令{$shell}执行失败";
// } else {
//     echo "shell命令{$shell}成功执行";
// }

    

