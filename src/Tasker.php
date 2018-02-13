<?php

class Tasker{
	private $work_num = 0;//最大工作进程数
	private $c_w_num = 0;//当前工作进程数
	private $sub_proc = [];//子进程ID
	private $is_subproc = false;//是否子进程
	private static $status = false;//是否受到信号
	private $can_new_pid = false;//是否可以创建子进程
	const VERSION = '1.0.0';//程序版本
	const PROCESS_NAME = 'Tasker';//程序名称

	public function __construct( $w_num ){
		$this->work_num = $w_num;
		$this->can_new_pid = function_exists('pcntl_fork');

		if($this->can_new_pid){//注册信号量处理函数
			declare(ticks = 1); //或者使用 pcntl_signal_dispatch() 手动执行信号量的处理（需放在合适的位置）
			pcntl_signal(SIGTERM, array($this,'sigHandler'),false );//array($this,'sigHandler')
			pcntl_signal(SIGHUP,  array($this,'sigHandler'),false );//如果第三个参数不填或者为true，会导致当主进程处于pcntl_wait时不能处理到来的信号量
			pcntl_signal(SIGUSR1, array($this,'sigHandler'),false );//如果第三个参数为false，可以打断pcntl_wait和sleep的阻塞
			pcntl_signal(SIGUSR2, array($this,'sigHandler'),false );
		}
	}
	
	public function sigHandler ( $signo ){
		switch ($signo) {
		     case SIGTERM:// 处理SIGTERM信号
		     case SIGHUP://处理SIGHUP信号
		     case SIGUSR1:
		     case SIGUSR2:
		     default:// 处理所有其他信号
		        echo "PID:".getmypid()." Caught SIG {$signo}...\n";
		        if($this->is_subproc){
			        self::$status = true;
		        }else{
			        echo "PID:".getmypid()." On Master Killing Sub Process...\n";
			        foreach( $this->sub_proc as $_pid =>$item ){//杀死子进程
				        $result = posix_kill($_pid, SIGTERM);
				        echo "PID:".getmypid()." Send SIG {$signo},Result:".($result?'SUCCESS':'FAIL')."\n";
				        $_status = null;
				        $dpid = pcntl_waitpid($_pid,$_status);//等待子进程退出
				        if( isset($this->sub_proc[$dpid]) ){//去掉子进程相关数据，以确保主进程会拉起子进程
							unset( $this->sub_proc[$dpid]);
							$this->c_w_num --;
						}
			        }
			        if( $signo==SIGTERM ){//等待子进程退出后自己退出
			        	echo "PID:".getmypid()." On Master Exiting...\n";
				        exit;
			        }
		        }
		}

	}

	public function run (){
		$this->updateProcLine('Master process');
		if( !$this->can_new_pid ){//未安装拓展,单进程处理
			
			exit();
		}
		//安装拓展 启动子进程处理
		while( !$this->is_subproc ){
			if($this->c_w_num >= $this->work_num){
				$_status = null;
				$dpid = pcntl_wait($_status);//会阻塞主进程 导致接收到信号量时，必须等到其中一个子进程退出才会进行信号量的处理
				if( isset($this->sub_proc[$dpid]) ){//去掉子进程相关数据，以确保主进程会拉起新子进程
					unset( $this->sub_proc[$dpid]);
					$this->c_w_num --;
				}
				continue;
			}
			echo 'PID:'.getmypid().' Fork...'.PHP_EOL;
			$pid = pcntl_fork();
			if( $pid== -1 ){
				echo 'Fork Fail'.PHP_EOL;
			}else if( $pid ){//父进程
				$this->sub_proc[$pid] = true;
				$this->c_w_num ++;
				print_r($this->sub_proc);
			}else{//子进程
				unset($this->sub_proc,$this->c_w_num , $this->work_num);
				$this->is_subproc = true;
				$this->updateProcLine('worker process');
				//TODO 子进程逻辑
				$k = rand(10,3000);
				while( $k-- ){
					if( self::$status ){
						echo 'PID:'.getmypid().' Exiting...'.PHP_EOL;
						exit(0);
					}
					echo 'PID:'.getmypid().' with '.$k.' times And Status:'. (self::$status ? 'False' : 'True').' is Running ...' .PHP_EOL;
					$i = 100000000;do{}while($i--);//sleep( 2 ); sleep 会被信号量打断
				}
				exit(0);
			}
		}
	}

	private function updateProcLine($status){
		$processTitle = self::PROCESS_NAME.'-' . self::VERSION . ': ' . $status;
		if(function_exists('cli_set_process_title') && PHP_OS !== 'Darwin') {
			cli_set_process_title($processTitle);
		}else if(function_exists('setproctitle')) {
			setproctitle($processTitle);
		}
	}
}

$task = new Task(20);
$task->run();
?>
