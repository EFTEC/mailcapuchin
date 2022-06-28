<?php 

namespace eftec\MailCapuchin;


use eftec\DaoOne;
use eftec\DashOne\controls\ButtonOne;
use eftec\DashOne\controls\ImageOne;
use eftec\DashOne\controls\LinkOne;
use eftec\DashOne\DashOne;
use eftec\ValidationOne;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailCapuchin
{
	var $config=[
		'dbserver'=>'127.0.0.1',
		'dbuser'=>'root',
		'dbpassword'=>'abc.123',
		'dbschema'=>'mailcapuchin',
		'mailserver'=>'127.0.0.1',
		'mailuser'=>'aa@aaa.com',
		'mailpassword'=>'abc.123',
		'mailsecurity'=>''
	];
	
	var $connected=false;
	/**
	 * @var DashOne 
	 */
	private $dash;
	
	/**
	 * @var DaoOne
	 */
	var $dao;
	/**
	 * @var ValidationOne
	 */
	var $validation;

	/**
	 * MailCapuchin constructor.
	 */
	public function __construct()
	{
		$this->loadConfig();
		
		$this->dao=new DaoOne($this->config['dbserver'],$this->config['dbuser'],$this->config['dbpassword'],$this->config['dbschema']);
		$this->connected= true;
		try {
			$this->dao->open();
		} catch(\Exception $ex) {
			$this->connected= false;
		}
		
		$this->validation=new ValidationOne();

		
	}
	
	public function addProject() {
		
	}
	public function runProject($rerun=false) {
		
	}
	public function genPixel() {
		
	}
	public function init() {
		//$sql='CREATE SCHEMA `mailcapuchin`';
		try {
			$this->dao->createTable('project',[
				'idproject'=>'INT NOT NULL AUTO_INCREMENT',
				'name'=>'VARCHAR(45) NULL',
				'date'=>'DATETIME NULL DEFAULT CURRENT_TIMESTAMP'
				],'idproject');
		} catch (\Exception $e) {
			//die($e->getMessage());
		}
		
		try {

			$this->dao->createTable('sendbox',[
				'idsendbox'=>'INT NOT NULL AUTO_INCREMENT',
				'idproject'=>'INT NULL',
				'idmail'=>'INT NULL',
				'date'=>'DATETIME NULL DEFAULT CURRENT_TIMESTAMP',
				'result'=>'VARCHAR(100) NULL'
			],'idsendbox');			

		} catch (\Exception $e) {
			//die($e->getMessage());
		}
		try {

			$this->dao->createTable('template',[
				'idtemplate'=>'INT NOT NULL AUTO_INCREMENT',
				'name'=>'VARCHAR(100) NULL',
				'title'=>'VARCHAR(400) NULL',
				'html'=>'MEDIUMTEXT',
				'txt'=>'MEDIUMTEXT'
			],'idtemplate');
		} catch (\Exception $e) {
			//die($e->getMessage());
		}

		try {

			$this->dao->createTable('listmail',[
				'idmail'=>'INT NOT NULL AUTO_INCREMENT',
				'idproject'=>'INT NULL',
				'email'=>'VARCHAR(200) NULL',
				'name'=>'VARCHAR(200) NULL',
				'date'=>'DATETIME NULL DEFAULT CURRENT_TIMESTAMP',
				'active'=>'VARCHAR(1) NULL',
				'var1'=>'VARCHAR(100) NULL',
				'var2'=>'VARCHAR(100) NULL',
				'var3'=>'VARCHAR(100) NULL'
			],'idmail');
		} catch (\Exception $e) {
			//die($e->getMessage());
		}
		try {
			$this->dao->createTable('configuration',[
				'idconfig'=>'INT NOT NULL',
				'email_server'=>'VARCHAR(45) NULL',
				'email_user'=>'VARCHAR(45) NULL',
				'email_password'=>'VARCHAR(45) NULL',
			],'idconfig');
		} catch (\Exception $e) {
			//die($e->getMessage());
		}		
	}
	function sendMail($idMail)
	{
		$mailObj=$this->queryMailDB(null,$idMail);
		
		$serverArr=explode(':',$this->config['mailserver'].':25');
		
		$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
		try {
			//Server settings
			$mail->SMTPDebug = 2;                                 // Enable verbose debug output
			$mail->isSMTP();                                      // Set mailer to use SMTP
			$mail->Host = $serverArr[0];  // Specify main and backup SMTP servers
			$mail->SMTPAuth = true;                               // Enable SMTP authentication
			$mail->Username = $this->config['mailuser'];                 // SMTP username
			$mail->Password = $this->config['mailpassword'];                           // SMTP password
			$mail->SMTPSecure = $this->config['mailsecurity'];                            // Enable TLS encryption, `ssl` also accepted
			$mail->Port = $serverArr[1];                                    // TCP port to connect to

			$mail->XMailer = "MailCapuchin sender";
			//Recipients
			$mail->setFrom($this->config['mailuser'], 'Mailer');
			$mail->addAddress($mailObj['email'], $mailObj['name']);     // Add a recipient
			$mail->addReplyTo($this->config['mailuser'], 'Information');

			$mail->isHTML(true);                                  // Set email format to HTML
			$mail->Subject ="Message send from Cornerrent";
			$mail->Body = "Message:<br>message<br>Email:{$mailObj['email']}<br>";
			$mail->AltBody =  "Message:\nEmail:{$mailObj['email']}";

			$mail->send();
			return true;
		} catch (Exception $e) {
			var_dump($e->errorMessage());
			return false;
		}
	}
	
	
	public function loadConfig() {
		$configFile=dirname($_SERVER['SCRIPT_FILENAME']).'/config.php';
		if (@file_exists($configFile)) {
			// if exists then we open
			$this->config=@include $configFile;
		} else {
			// we create a new one using the default configuration
			$this->saveConfig();
		}
	}
	public function saveConfig() {
		$r="<?php\n// Configuration File:\nreturn ".var_export($this->config,true).';';
		return file_put_contents(dirname($_SERVER['SCRIPT_FILENAME']).'/config.php',$r);
	}

	public function queryProjectDB($idproject=null) {
		if ($idproject==null) {
			return $this->dao->select("*")->from("project")->toList();
		} else {
			return $this->dao->select("*")->from("project")
				->where('idproject',$idproject)->first();
		}
	}
	public function listSendBox($idproject,$result='') {

		if(!$result) {
			return $this->dao->select("sendbox.date,sendbox.result,listmail.*")
				->from("sendbox")
				->innerjoin("listmail", "sendbox.idmail=listmail.idmail")
				->where('sendbox.idproject', $idproject)
				->where('sendbox.result<>?','pending')
				->toList();
		} else {
			return $this->dao->select("sendbox.date,sendbox.result,listmail.*")
				->from("sendbox")
				->innerjoin("listmail", "sendbox.idmail=listmail.idmail")
				->where('sendbox.idproject', $idproject)
				->where('sendbox.result',$result)
				->toList();			
		}
	}
	public function queryMailDB($idproject=null,$idmail=null,$result=null) {
		if ($idproject!=null) {
			if ($result) {
				return $this->dao->select("*")->from("listmail")
					->where(['result'=>$result])
					->where(['idproject' => $idproject])->toList();

			} else {
				return $this->dao->select("*")->from("listmail")
					->where(['idproject' => $idproject])->toList();
				
			}
		} 
		if ($idmail!=null) {
			return $this->dao->select("*")->from("listmail")
				->where(['idmail' => $idmail])->first();			
		}
	}
	public function queryTemplateDB($idtemplate=null) {
		if ($idtemplate==null) {
			return $this->dao->select("*")->from("template")->toList();
		} else {
			return $this->dao->select("*")->from("template")->where('idtemplate',$idtemplate)->first();
		}
	}

	/**
	 * @throws \Exception
	 */
	public function showUI() {
		$op=@$_GET['_op'];
		$id=@$_GET['_id'];
		
		$this->init();

		// common
		if ($this->connected) {
			$projects=$this->queryProjectDB();	
		} else {
			$projects=[];
		}
		
	


		$links=[];
		$links[]=new LinkOne("List Project","?_op=listp","fas fa-tasks");
		$links[]=new LinkOne("List Templates","?_op=listtemplate","fas fa-tasks");
		$links[]=new LinkOne("Configuration","?_op=editconfig","fas fa-sliders-h");

		foreach($projects as &$project) {
			$links[]="<hr>";
			$links[]=new LinkOne("Project".$project['name'],"?_op=listp","far fa-star");
			$links[] = (new LinkOne("MailList", "?_op=listm&_id=".$project['idproject'], "fas fa-at"))->addClass('ml-4');
			$links[] = (new LinkOne("Sendbox", "?_op=listsb&_id=".$project['idproject'], "fas fa-envelope"))->addClass('ml-4');
			$links[] = (new LinkOne("Result", "?_op=listre&_id=".$project['idproject'], "fas fa-envelope-open"))->addClass('ml-4');
		}
		
		
		$this->dash=new DashOne();

		$this->dash->head('Example - test 1');
		$this->dash->menuUpper([new ImageOne('https://via.placeholder.com/32x32')," - ",new LinkOne('Mail Capuchin','#')]);
		$this->dash
			->startcontent()
			->menu($links)
			->startmain();
		switch ($op) {
			case '':
			case 'listp':
				$this->listpController($projects);
				break;
			case 'listtemplate':
				$this->listTemplateController();
				break;
			case 'edittemplate':
				$this->editTemplateController($id);
				break;
			case 'editconfig':
				$this->editConfigController();
				break;				
			case 'editproject':
				$this->editProjectUI($id);
				break;
			case 'editmail':
				$this->editMailController($id);
				break;				
			case 'listm':
				$this->listmController($id);
				break;
			case 'listsb':
				$this->listsbController($id,$projects[$id]);
				break;
			case 'listre':
				$this->listreController($id,$projects[$id]);
				break;				
		}
		$this->dash
			->endmain()
			->endcontent();
		$this->dash->footer();
		$this->dash->render();
	}

	/**
	 
	 * @param $projects
	 * @throws \Exception
	 */
	public function listpController( $projects) {
		$buttonAdd=$this->validation->def('')->post('Add');
		if ($buttonAdd) {
			$this->dao
				->from('project')
				->set('name=?','-new project-')
				->insert();
			$projects=$this->queryProjectDB(); // re-read
		}		
		foreach ($projects as &$project) {
			$project['Menu']=new LinkOne('Edit','?_op=editproject&_id='.$project['idproject']);
		}		
		$this->listpView($projects);
	}
	/**
	 
	 * @param $projects
	 * @throws \Exception
	 */
	public function listpView($projects) {
		$this->dash->title('Projects')
			->table($projects,['name'=>'Name','date'=>'Date Creation','Menu'=>'Menu']);
		$this->dash->buttons([new ButtonOne("Add","Add","btn btn-danger")]);
	}

	/**
	 
	 * @throws \Exception
	 */
	public function listTemplateController() {
		$add=$this->validation->def('')->type('varchar')->post('Add');
		if ($add) {
			$this->dao
				->from('template')
				->set('name=?','-new-')
				->set('html=?','')
				->set('txt=?','')
				->insert();
			
		} 
		$templates=$this->queryTemplateDB();
		foreach ($templates as &$template) {
			$template['Menu']=new LinkOne('Edit','?_op=edittemplate&_id='.$template['idtemplate']);
		}
		$this->listTemplateView($templates);
	}

	/**
	 
	 * @param $templates
	 */
	public function listTemplateView($templates) {
		$this->dash->title('Templates')
			->table($templates,['idtemplate','name','Menu']);
		$this->dash->buttons([new ButtonOne("Add","Add","btn btn-danger")]);
	}

	/**
	 
	 * @param $idmail
	 * @throws \Exception
	 */
	public function editMailController( $idmail) {
		$formData=$this->queryMailDB(null,$idmail);
		if ($this->dash->isPostBack()) {
			$modify=$this->validation->def('')->type('varchar')->post('Modify');
			$delete=$this->validation->def('')->type('varchar')->post('Delete');
			$back=$this->validation->def('')->type('varchar')->post('Back');
			if ($modify) {
				$formData['idmail'] = $this->validation->def('')->type('varchar')->post('idmail');
				$formData['idproject'] = $this->validation->def('')->type('varchar')->post('idproject');
				$formData['email'] = $this->validation->def('')->type('varchar')->post('email');
				$formData['name'] = $this->validation->def('')->type('varchar')->post('name');
				$formData['active'] = $this->validation->def('')->type('varchar')->post('active');
				$formData['var1'] = $this->validation->def('')->type('varchar')->post('var1');
				$formData['var2'] = $this->validation->def('')->type('varchar')->post('var2');
				$formData['var3'] = $this->validation->def('')->type('varchar')->post('var3');
				$this->dao
					->from('listmail')
					->set('idproject=?', $formData['idproject'])
					->set('`email`=?', $formData['email'])
					->set('name=?', $formData['name'])
					->set('active=?', $formData['active'])
					->set('var1=?', $formData['var1'])
					->set('var2=?', $formData['var2'])
					->set('var3=?', $formData['var3'])
					->where('idmail=?', $formData['idmail'])->update();
				// and done...
				header('Location:?_op=listm&_id='.$formData['idproject']);
				die(1);
			}
			if ($delete) {
				$this->dao
					->from('listmail')
					->where('idmail=?',$idmail)->delete();
				// and done...
				header('Location:?_op=listm&_id='.$formData['idproject']);
				die(1);
			}
			if ($back) {
				header('Location:?_op=listm&_id='.$formData['idproject']);
				die(1);

			}

		} else {
			$this->editMailView($idmail,$formData);

		}

	}

	/**
	 
	 * @param $idmail
	 * @param $formData
	 */
	public function editMailView( $idmail,$formData) {
		$formDef=['idmail'=>'hidden'
			,'idproject'=>'hidden'
			,'email'=>'text'
			,'name'=>'text'
			,'active'=>'text'
			,'var1'=>'text'
			,'var2'=>'text'
			,'var3'=>'text'];
		$formComment=null; //['name'=>'Name of the project'];
		$this->dash->title('Edit Mail');
		$this->dash->form($formData,$formDef,$formComment);
		$this->dash->buttons([new ButtonOne("Modify","Modify","btn btn-primary")
			,new ButtonOne("Delete","Delete","btn btn-danger")
			,new ButtonOne("Back","Return Back","btn btn-primary")],true);
	}

	/**
	 
	 * @param $id
	 * @throws \Exception
	 */
	public function editProjectUI($id) {
		
		if ($this->dash->isPostBack()) {
			$modify=$this->validation->def('')->type('varchar')->post('Modify');
			$delete=$this->validation->def('')->type('varchar')->post('Delete');
			$back=$this->validation->def('')->type('varchar')->post('Back');
			if ($modify) {
				$formData['idproject'] = $this->validation->def('')->type('varchar')->post('idproject');
				$formData['name'] = $this->validation->def('')->type('varchar')->post('name');
				$this->dao
					->from('project')
					->set('name=?', $formData['name'])
					->where('idproject=?', $formData['idproject'])->update();
				// and done...
				header('Location:?_op=listp');
				die(1);
			}
			if ($delete) {
				$this->dao
					->from('project')
					->where('idproject=?',$id)->delete();
				// and done...
				header('Location:?_op=listp');
				die(1);
			}
			if ($back) {
				header('Location:?_op=listp');
				die(1);
				
			}
			
		} else {
			$formData=$this->queryProjectDB($id);
			$this->editProjectView($id,$formData);
		}

	}

	/**
	 
	 * @param $id
	 * @throws \Exception
	 */
	public function editProjectView($id,$formData) {
		$formDef=['idproject'=>'hidden','name'=>'text','date'=>'hidden'];
		$formComment=['name'=>'Name of the project'];
		$this->dash->title('Edit Project');
		$this->dash->form($formData,$formDef,$formComment);
		$this->dash->buttons([new ButtonOne("Modify","Modify","btn btn-primary")
			,new ButtonOne("Delete","Delete","btn btn-danger")
			,new ButtonOne("Back","Return Back","btn btn-primary")],true);
	}

	/**
	 
	 * @throws \Exception
	 */
	public function editConfigController() {
		$message='';
		if ($this->dash->isPostBack()) {
			$button1=$this->validation->def('')->type('varchar')->post('Modify');
			$this->config['dbserver']=$this->validation->def('')->type('varchar')->post('dbserver');
			$this->config['dbuser']=$this->validation->def('')->type('varchar')->post('dbuser');
			$this->config['dbpassword']=$this->validation->def('')->type('varchar')->post('dbpassword');
			$this->config['dbschema']=$this->validation->def('')->type('varchar')->post('dbschema');
			$this->config['mailserver']=$this->validation->def('')->type('varchar')->post('mailserver');
			$this->config['mailuser']=$this->validation->def('')->type('varchar')->post('mailuser');
			$this->config['mailpassword']=$this->validation->def('')->type('varchar')->post('mailpassword');
			$this->config['mailsecurity']=$this->validation->def('')->type('varchar')->post('mailsecurity');
			$this->saveConfig();
			
			@$this->dao->close();
			$this->dao=new DaoOne($this->config['dbserver'],$this->config['dbuser'],$this->config['dbpassword'],$this->config['dbschema']);

			$this->connected= true;
			try {
				$this->dao->open();
				$message="Connected to ".$this->config['dbschema'];
			} catch(\Exception $ex) {
				$message=$ex->getMessage();
				$this->connected= false;
			}

		}
		$this->editConfigView($message);
	}

	/**
	 
	 * @param $message
	 */
	public function editConfigView($message) {
		$formDef=[
			'dbserver'=>'text',
			'dbuser'=>'text',
			'dbpassword'=>'password',
			'dbschema'=>'text',
			'mailserver'=>'text',
			'mailuser'=>'text',
			'mailpassword'=>'password',
			'mailsecurity'=>'text'
		];
		$this->dash->title('Edit Configuration');
		$this->dash->form($this->config,$formDef);
		$this->dash->buttons([new ButtonOne("Modify","Modify","btn btn-primary")
			,new ButtonOne("Delete","Delete","btn btn-danger"),
			new ButtonOne("Back","Return Back","btn btn-primary")],true);
		if ($message) {
			$this->dash->alert($message);
		}
	}
	
	/**
	 
	 * @param $id
	 * @throws \Exception
	 */
	public function editTemplateController( $id) {
		if ($this->dash->isPostBack()) {
			// some button was pressed
			$modify=$this->validation->def('')->type('varchar')->post('Modify');
			$delete=$this->validation->def('')->type('varchar')->post('Delete');
			$back=$this->validation->def('')->type('varchar')->post('Back');			
			if ($modify) {
				$formData['idtemplate'] = $this->validation->def('')->type('varchar')->post('idtemplate');
				$formData['name'] = $this->validation->def('')->type('varchar')->post('name');
				$formData['title'] = $this->validation->def('')->type('varchar')->post('title');
				$formData['html'] = $this->validation->def('')->type('varchar')->post('html');
				$formData['txt'] = $this->validation->def('')->type('varchar')->post('txt');
				$this->dao
					->from('template')
					->set('name=?', $formData['name'])
					->set('title=?', $formData['title'])
					->set('html=?', $formData['html'])
					->set('txt=?', $formData['txt'])
					->where('idtemplate=?', $formData['idtemplate'])->update();
				// and done...
				header('Location:?_op=listtemplate');
				die(1);
			}
			if ($delete) {
				$this->dao
					->from('template')
					->where('idtemplate=?',$id)->delete();
				// and done...
				header('Location:?_op=listtemplate');
				die(1);
			}
			if ($back) {
				header('Location:?_op=listtemplate');
				die(1);

			}			
		} else {
			$formData=$this->queryTemplateDB($id);
			$this->editTemplateView($formData,$this->dash,$id);
		}
	}

	/**
	 * @param $formData
	 
	 * @param $id
	 */
	public function editTemplateView($formData, $id) {
		$formDef=['idtemplate'=>'hidden','name'=>'text','title'=>'text'
				,'html'=>'textarea','txt'=>'textarea'];
		$formComment=['name'=>'Name of the template','title'=>''
			,'html'=>'variables:%name,%email,%var1,%var2,%var3'
			,'txt'=>'variables:%name,%email,%var1,%var2,%var3'];
		$this->dash->title('Edit Template');
		$this->dash->form($formData,$formDef,$formComment);
		$this->dash->buttons([new ButtonOne("Modify","Modify","btn btn-primary")
			,new ButtonOne("Delete","Delete","btn btn-danger"),
			new ButtonOne("Back","Return Back","btn btn-primary")],true);
	}
	/**
	 
	 * @param $idproject
	 * @throws \Exception
	 */
	public function listmController( $idproject) {
		$mailList=$this->queryMailDB($idproject);
		if ($this->dash->isPostBack()) {
			$formMasive['Masive']=$this->validation->def('')->post('Masive');
			$button2=$this->validation->def('')->post('button2');
			$button1=$this->validation->def('')->post('button1');
			$button3=$this->validation->def('')->post('button3');
			if ($button1) {
				// masive
				$txt=$this->validation->def('')->post('Masive');
				$txt=str_replace("\r\n","\n",$txt);
				$list=explode("\n",$txt);
				$separator=(strpos($list[0],'|')!==false)?'|':',';
				foreach($list as $item) {
					$item.=$separator.$separator.$separator.$separator.$separator;
					$cell=explode($separator,$item);
					$this->dao
						->from('listmail')
						->set('idproject=?',$idproject)
						->set('`email`=?',$cell[0])
						->set('name=?',$cell[1])
						->set('active=?','1')
						->set('var1=?',$cell[2])
						->set('var2=?',$cell[3])
						->set('var3=?',$cell[4])
						->insert();
				}
			}
			if ($button2) {
				$this->dao
					->from('listmail')
					->set('idproject=?',$idproject)
					->set('`email`=?','--pending--')
					->set('name=?','--new--')
					->set('active=?','1')
					->set('var1=?','')
					->set('var2=?','')
					->set('var3=?','')
					->insert();
			}
			if ($button3) {
				foreach($mailList as $mail) {
					$c=$this->dao
						->select('count(*) c')
						->from('sendbox')
						->where('idproject=?',$idproject)
						->where('idmail=?',$mail['idmail'])
						->where("result='PENDING'")
						->firstScalar();
					if ($c!=1) {
						// we don't add duplicates
						$this->dao
							->from('sendbox')
							->set('idproject=?', $idproject)
							->set('idmail=?', $mail['idmail'])
							->set('result=?', 'PENDING')
							->insert();
					}
				}
			}
		}
		$this->listmView($mailList);
	}

	/**
	 * Mail View
	 
	 * @param $mailList
	 */
	public function listmView($mailList) {
		
		foreach ($mailList as &$mail) {
			$mail['Menu']=new LinkOne('Edit','?_op=editmail&_id='.$mail['idmail']);
		}
		$mailDef=['email','name','date','active','var1','var2','var3','Menu'];
		//from 	name 	date 	active 	var1 	var2 	var3 	Menu
		$this->dash->title('Mail List')
			->table($mailList,$mailDef);

		$formMasive=['Masive'=>''];
		$formMasiveDef=['Masive'=>'textarea'];
		$formMasiveMsg=['Masive'=>'email|name|var1|var2|var3 or email,name,var1,var2,var3'];


		$this->dash->form($formMasive,$formMasiveDef,$formMasiveMsg);
		$this->dash->buttons([
			new ButtonOne("button1","Add Masive","btn btn-primary")
			,new ButtonOne("button2","Add New","btn btn-danger")
			,new ButtonOne("button3","Send all","btn btn-danger")],true);
	}

	/**
	 * @param $idProject
	 * @param $project
	 * @throws \Exception
	 */
	public function listsbController( $idProject, $project) {
		$mailList=$this->listSendBox($idProject,'PENDING');
		if ($this->dash->isPostBack()) {
			
			$button1=$this->validation->def('')->post('button1');
			$button2=$this->validation->def('')->post('button2');
			if ($button1) {
				$this->sendMail($mailList[0]['idmail']);
			}
			if ($button2) {
				
				$this->dao->from('sendbox')
					->set(['result'=>'ABORT'])
					->where('idproject=?',$idProject)
					->update();
				var_dump($this->dao->lastQuery);
				
				$mailList=$this->listSendBox($idProject,'PENDING');
			}
		}
		
		$this->listsbView($idProject,$project,$mailList);
	}

	/**
	 
	 * @param $id
	 * @param $project
	 * @param $mailList
	 */
	private function listsbView( $id, $project,$mailList)
	{
		foreach ($mailList as &$mail) {
			$mail['Menu']=new LinkOne('Edit','?_op=editsendbox&_id='.$mail['idmail']);
		}
		$this->dash->title('Sendbox - <small class="text-muted">'.$project['name'].'</small>')
			->table($mailList);
		$this->dash->buttons([
			new ButtonOne("button1","Send","btn btn-primary")
			,new ButtonOne("button2","Purge All","btn btn-danger")],false);		
	}

	/**
	 
	 * @param $id
	 * @param $project
	 */
	public function listreController( $id, $project) {
		$mailList=$this->listSendBox($id,'');
		foreach ($mailList as &$mail) {
			$mail['Menu']=new LinkOne('Edit','?_op=editsendbox&_id='.$mail['idmail']);
		}
		$this->listreView( $id, $project,$mailList);
	}

	/**
	 
	 * @param $id
	 * @param $project
	 * @param $mailList
	 */
	public function listreView( $id, $project,$mailList) {
		$this->dash->title('Result - <small class="text-muted">'.$project['name'].'</small>')
			->table($mailList);
	}
}