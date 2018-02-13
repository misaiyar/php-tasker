<?php
interface JobInterface{
    public function popJob($queue);
    public function pushJob($queue,$data);
    public function fininshJob($jobid);
    public function failedJob($jobid);
    
} 

?>
