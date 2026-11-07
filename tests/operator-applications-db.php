<?php
declare(strict_types=1);
namespace GDO\DB { final class Database {public static $instance;public $link;public static function instance(){return self::$instance;}public function getLink(){return $this->link;}} }
namespace {
if(PHP_SAPI!=='cli')exit(1);
$socket=getenv('GASTRO_QA_SOCKET')?:'';
if(!str_starts_with($socket,'/tmp/simion-gastro-db-'))throw new RuntimeException('Explicit isolated socket required');
require dirname(__DIR__).'/Commerce/OperatorApplications.php';
use GDO\LinkUUp\Commerce\OperatorApplications as A;
$db=new mysqli('localhost',getenv('GASTRO_QA_DB_USER')?:getenv('USER'),'','',0,$socket);
$schema='simion_operator_qa_'.bin2hex(random_bytes(6));$db->query('CREATE DATABASE `'.$schema.'`');$db->select_db($schema);
$bridge=new \GDO\DB\Database();$bridge->link=$db;\GDO\DB\Database::$instance=$bridge;
$n=0;function check(bool $ok):void{global $n;if(!$ok)throw new Exception('Check failed #'.($n+1));++$n;}
function denied(callable $f,int $code):void{try{$f();throw new LogicException('Expected denial');}catch(RuntimeException $e){check($e->getCode()===$code);}}
try{
$db->query('CREATE TABLE gdo_user(user_id BIGINT PRIMARY KEY,user_type VARCHAR(20),user_deleted DATETIME NULL) ENGINE=InnoDB');
$db->query("INSERT INTO gdo_user VALUES(1,'member',NULL),(2,'member',NULL),(3,'member',NULL),(4,'guest',NULL)");
$db->query('CREATE TABLE lup_room(room_id BIGINT PRIMARY KEY,room_owner BIGINT,room_deleted DATETIME NULL) ENGINE=InnoDB');$db->query('INSERT INTO lup_room VALUES(10,1,NULL),(20,2,NULL)');
$db->query('CREATE TABLE gdo_permission(perm_id BIGINT PRIMARY KEY,perm_name VARCHAR(30)) ENGINE=InnoDB');$db->query("INSERT INTO gdo_permission VALUES(1,'admin')");
$db->query('CREATE TABLE gdo_userpermission(perm_user_id BIGINT,perm_perm_id BIGINT,PRIMARY KEY(perm_user_id,perm_perm_id)) ENGINE=InnoDB');$db->query('INSERT INTO gdo_userpermission VALUES(3,1),(1,1)');
$db->query('CREATE TABLE lup_operatorapplication(oa_id BIGINT PRIMARY KEY AUTO_INCREMENT,oa_user BIGINT,oa_room BIGINT NULL,oa_business VARCHAR(160),oa_kind VARCHAR(100),oa_address VARCHAR(300),oa_contact VARCHAR(160),oa_email VARCHAR(254),oa_phone VARCHAR(80),oa_details TEXT,oa_status VARCHAR(20),oa_version INT,oa_created DATETIME,oa_reviewed DATETIME NULL,oa_reviewer BIGINT NULL,oa_review_note TEXT,oa_history TEXT) ENGINE=InnoDB');
$v=['business'=>'QA Betrieb','kind'=>'Bildung','address'=>'QA Adresse','contact'=>'QA Kontakt','email'=>'qa@example.invalid','phone'=>'','details'=>'QA Leitung'];
denied(fn()=>A::submit('4',null,$v),403);denied(fn()=>A::submit('1','20',$v),403);
$id=A::submit('1','10',$v);check(!A::approvalFor('1','10'));denied(fn()=>A::submit('1','10',$v),409);
denied(fn()=>A::decision('1',$id,1,'approved','Geprüft',true),403);denied(fn()=>A::decision('2',$id,1,'approved','Geprüft',true),403);
denied(fn()=>A::decision('3',$id,1,'approved','Geprüft',false),400);
A::decision('3',$id,1,'approved','Prüfung und Vertragsklärung manuell abgeschlossen',true);check(A::approvalFor('1','10'));check(!A::approvalFor('2','10'));
$db->begin_transaction();check(A::approvalFor('1','10'));denied(fn()=>A::submit('2','20',$v),503);
$other=new mysqli('localhost',getenv('GASTRO_QA_DB_USER')?:getenv('USER'),'',$schema,0,$socket);$other->query('SET innodb_lock_wait_timeout=1');
try{$other->query("UPDATE lup_operatorapplication SET oa_status='revoked' WHERE oa_id=".(int)$id);throw new LogicException('Approval row was not locked');}catch(mysqli_sql_exception $e){check($e->getCode()===1205);}
$db->rollback();$other->close();
denied(fn()=>A::decision('3',$id,1,'revoked','Veraltet',false),409);denied(fn()=>A::submit('1','10',$v),409);
$db->query('UPDATE lup_room SET room_owner=2 WHERE room_id=10');check(!A::approvalFor('1','10'));$db->query('UPDATE lup_room SET room_owner=1 WHERE room_id=10');
$db->query('UPDATE gdo_user SET user_deleted=UTC_TIMESTAMP() WHERE user_id=1');check(!A::approvalFor('1','10'));
A::decision('3',$id,2,'revoked','Konto deaktiviert',false);check(!A::approvalFor('1','10'));$db->query('UPDATE gdo_user SET user_deleted=NULL WHERE user_id=1');
$id2=A::submit('1',null,$v);denied(fn()=>A::decision('3',$id2,1,'approved','Prüfung',true),409);
check(count(A::applications('2'))===0);denied(fn()=>A::applications('2',true),403);check(count(A::applications('3',true))===2);
$db->query('ALTER TABLE lup_operatorapplication ENGINE=MyISAM');check(!A::approvalFor('1','10'));denied(fn()=>A::submit('2','20',$v),503);
echo "$n isolated DB service checks passed (synthetic schema including competing approval write; not HTTP/GDO schema).\n";
}finally{$db->query('DROP DATABASE `'.$schema.'`');}
}
