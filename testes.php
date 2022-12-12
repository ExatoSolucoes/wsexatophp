<?php

// carregando a biblioteca dos webservices
require_once('WSExato.php');

// definindo valores (fornecidos pela Exato Soluções)
const URLWS = 'url do serviço';
const USWS = 'nome de usuário';
const CHWS = 'chave do usuário';
const CLCONC = 'identificador do cliente';

// criando o objeto de requisição
$ws = new \WSExato(URLWS, USWS);

// escolha o serviço a consultar
$servico = 'conciliação erp'; // conciliar informações do sistema de vendas
//$servico = 'extrato de movimentação'; // recuperar extrato de movimentação
//$servico = 'conciliação bancária'; // conciliar informações de extrato bancário
//$servico = 'extrato bancário'; // enviar extrato bancário
//$servico = 'quitação'; // conferir a quitação de parcelas

// consultando de acordo com o serviço
switch ($servico) {
		
	case 'conciliação erp':
		// preparando as variáveis de requisição
		$vars = [
			'c' => CLCONC, // o identificador do cliente, fornecido pela Exato Soluções
			't' => 'venda', // o tipo de lançamento: "venda" ou "pagamento"
			'req' => file_get_contents('exemploerp.json'), // o texto da requisição com os lançamentos
		];
		
		// variável opctional para receber o resultado já descompactado
		$vars['fr'] = 'txt';
		
		// texto da chave de requisição (a chave de usuário é adicionada automaticamente, junto à codificação MD5)
		$k = USWS . $vars['c'] . $vars['t'] . $vars['req'];
		
		// requisitando o serviço
		$resp = $ws->requisitar('vdk-cartoes/conciliacao-erp', CHWS, $k, $vars);
		break;
		
	case 'extrato de movimentação':
		// preparando as variáveis de requisição
		$vars = [
			'id' => CLCONC, // o identificador do cliente, fornecido pela Exato Soluções
			'tp' => 'venda', // o tipo de lançamento: "venda" ou "pagamento"
			'dini' => '01/03/2022', // a data inicial do período
			'dfim' => '05/03/2022', // a data final do período
			'l' => 'exato.json', // o layout da lista recebida
		];
		
		// variável opctional para receber o resultado já descompactado
		$vars['fr'] = 'txt';
		
		// texto da chave de requisição (a chave de usuário é adicionada automaticamente, junto à codificação MD5)
		$k = $vars['id'] . $vars['dini'] . $vars['dfim'] . $vars['tp'];
		
		// requisitando o serviço
		$resp = $ws->requisitar('vdk-cartoes/extrato-recuperacao', CHWS, $k, $vars);
		break;
		
	case 'conciliação bancária':
		// preparando as variáveis de requisição
		$vars = [
			'c' => CLCONC, // o identificador do cliente, fornecido pela Exato Soluções
			'e' => file_get_contents('caminho para o extrato cnab ou ofx'), // o texto do extrato (OFX ou CNAB)
			'b' => 'conta bancária', // conta bancária no formato banco-agência-conta
			'q' => 'sim', // retornar quitação bancária?
		];
		
		// variável opctional para receber o resultado já descompactado
		$vars['fr'] = 'txt';
		
		// texto da chave de requisição (a chave de usuário é adicionada automaticamente, junto à codificação MD5)
		$k = USWS . $vars['c'] . $vars['b'] . $vars['e'];
		
		// requisitando o serviço
		$resp = $ws->requisitar('vdk-cartoes/bancario', CHWS, $k, $vars);
		break;
		
	case 'extrato bancário':
		// preparando as variáveis de requisição
		$vars = [
			'c' => CLCONC, // o identificador do cliente, fornecido pela Exato Soluções
			'e' => file_get_contents('caminho para o extrato cnab ou ofx'), // o texto do extrato (OFX ou CNAB)
			'b' => 'conta bancária', // conta bancária no formato banco-agência-conta
			'm' => 'não', // conta bancária no formato banco-agência-conta
		];
		
		// variável opctional para receber o resultado já descompactado
		$vars['fr'] = 'txt';
		
		// texto da chave de requisição (a chave de usuário é adicionada automaticamente, junto à codificação MD5)
		$k = USWS . $vars['c'] . $vars['e'];
		
		// requisitando o serviço
		$resp = $ws->requisitar('vdk-cartoes/extrato-bancario', CHWS, $k, $vars);
		break;
		
	case 'quitação':
		// preparando as variáveis de requisição
		$vars = [
			'c' => CLCONC, // o identificador do cliente, fornecido pela Exato Soluções
			'd' => '05/07/2022', // data a consultar no formato DD/MM/AAAA ou AAAA-MM-DD
			'cn' => '00000000000000', // CNPJ da loja (não enviar ou deixar em branco para todas as do cliente)
		];
		
		// variável opctional para receber o resultado já descompactado
		$vars['fr'] = 'txt';
		
		// texto da chave de requisição (a chave de usuário é adicionada automaticamente, junto à codificação MD5)
		$k = USWS . $vars['c'] . $vars['d'];
		
		// requisitando o serviço
		$resp = $ws->requisitar('vdk-cartoes/quitacao', CHWS, $k, $vars);
		break;
}

// resposta
echo('<strong>SERVIÇO CONSULTADO: '.mb_strtoupper($servico).'</strong><br />');
echo('<p>Log da requisição<ul>');
	$log = $ws->recLog();
	foreach ($log as $l) echo('<li>'.$l.'</li>');
echo('</ul></p>');
echo('<p>Variáveis enviadas<ul>');
	foreach ($vars as $k => $v) echo('<li><strong>'.$k.':</strong> '.$v.'</i>');
echo('</ul></p>');
echo('<p><strong>Chamado encerrado com erro '.$resp['e'].' ('.$resp['msg'].'). A resposta foi gravada no arquivo "resposta.json".</strong></p>');
file_put_contents('resposta.json', $ws->resposta());