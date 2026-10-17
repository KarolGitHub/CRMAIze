<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$db = new CRMAIze\Service\DatabaseService();
echo "pgsql: ", var_export($db->isPostgreSQL(), true), "\n";
$r = (new ReflectionClass($db))->getProperty('pdo'); $r->setAccessible(true); $p = $r->getValue($db);
echo $p->query('select current_database() d, version() v')->fetch()['v'], "\n";
echo "tables: ", $p->query("select count(*) c from information_schema.tables where table_schema='public'")->fetch()['c'], "\n";
$p->exec("create table if not exists _smoke(id int)"); $p->exec("insert into _smoke values (1)");
echo "rw: ", $p->query("select count(*) c from _smoke")->fetch()['c'], "\n"; $p->exec("drop table _smoke");
