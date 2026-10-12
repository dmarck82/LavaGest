<?php
declare(strict_types=1);
use LavaGest\Controladores\VeiculoControlador;
use LavaGest\Seguranca\Autenticador;
function processarRotasVeiculos(string $metodo,string $caminho,VeiculoControlador $c,Autenticador $a):bool{
    $p=explode('/',trim($caminho,'/')); $id=count($p)===2&&$p[0]==='veiculos'&&ctype_digit($p[1]);
    if(($p[0]??'')!=='veiculos')return false; $a->autenticar();
    if($metodo==='GET'&&$caminho==='/veiculos'){echo json_encode($c->listarVeiculos());return true;}
    if($metodo==='POST'&&$caminho==='/veiculos'){$d=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);http_response_code(201);echo json_encode($c->criarVeiculo($d));return true;}
    if($metodo==='GET'&&$id){echo json_encode($c->buscarVeiculo((int)$p[1]));return true;}
    if($metodo==='PUT'&&$id){$d=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);echo json_encode($c->atualizarVeiculo((int)$p[1],$d));return true;}
    if($metodo==='DELETE'&&$id){$c->deletarVeiculo((int)$p[1]);http_response_code(204);return true;} return false;
}
