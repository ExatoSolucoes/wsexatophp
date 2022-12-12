<?php

/**
 * Acesso a webservices da Exato Soluções (exatosolucoes.com.br)
 * @author Lucas Junqueira <lucas@exatosolucoes.com.br>
 * @version 1.0
 */
class WSExato {
	
	/**
	 * endereço de acesso aos serviços
	 */
	private $url;
	
	/**
	 * usuário da requisição
	 */
	private $usuario;
	
	/**
	 * log de requisição
	 */
	private $log = [ ];
	
	/**
	 * texto original de resposta recebido
	 */
	private $original = '';
	
	/**
	 * Construtor do acesso aos webservices.
	 * @param string $url endereço de acesso aos webservices
	 * @param string $us usuário de acesso
	 */
	public function __construct($url, $us)
	{
		$this->url = $url;
		$this->usuario = $us;
	}
	
	/**
	 * Faz uma chamada a um webservice.
	 * @param string $rota a rota do serviço
	 * @param string $chave a chave de 32 caracteres do usuário
	 * @param string $k o texto a ser usado na formação da variável "k" (sem a chave)
	 * @param array $vars array associativo com as variáveis usadas na requisição ("r", "u" e "k" são adicionadas automaticamente)
	 * @return array array associativo com a resposta, incluindo o código de erro "e" e uma mensagem explicativa "msg"
	 */
	public function requisitar($rota, $chave, $k, $vars)
	{
		// preparando log/erro
		$erro = 0;
		$this->log = [ ];
		$this->adLog('início da requisição');
		
		// validando rota
		if (strpos($rota, '/') === false) {
			$this->adLog('a rota indicada (' . $rota . ') é inválida');
			$erro = -10;
		} else {
			// seguindo a requisição
			$this->adLog('rota definida como ' . $rota);
			
			// criando chave
			$k = md5($chave . $k);
			$this->adLog('chave de acesso definida como ' . $k);

			// repassando valores
			$vars['r'] = $rota;
			$vars['u'] = $this->usuario;
			$vars['k'] = $k;

			// preparando chamada
			$ch = curl_init();

			// definindo a url
			curl_setopt($ch, CURLOPT_URL, $this->url);

			// retornando texto ao invés de exibir a resposta
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// incluindo os valores
			$valores = [ ];
			foreach ($vars as $key=>$value) $valores[] = $key . '=' . urlencode($value);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $valores));

			// acessando o webservice
			$this->adLog('conectando ao webservice em ' . $this->url);
			$resposta = curl_exec($ch);

			// fechando a conexão
			$this->adLog('resposta do webservice recebida');
			curl_close($ch);
			
			// conferindo a resposta
			if ($resposta === false) {
				// requisição sem sucesso
				$this->adLog('erro ao acessar o webservice');
				$erro = -11;
				$this->original = '';
			} else {
				// validando
				$this->original = $resposta;
				$json = json_decode($resposta, true);
				if (json_last_error() == JSON_ERROR_NONE) {
					// resposta válida?
					if (isset($json['e'])) {
						$erro = $json['e'];
						$ret = $json;
						$this->adLog('resposta do webservice validada');
					} else {
						$erro = -13;
						$this->adLog('resposta do webservice corrompida (falta "e")');
					}
				} else {
					// resposta corrompida
					$this->adLog('resposta do webservice corrompida');
					$erro = -12;
				}
			}
		}
		
		// finalizando
		$this->adLog('fim da requisição');
		if ($erro == 0) {
			// retornar resposta do webservice
			$ret['e'] = 0;
			$ret['msg'] = 'requisição finalizada com sucesso';
			return ($ret);
		} else {
			// retornar erro da requisição
			$ret = [ 'e' => $erro ];
			switch ($erro) {
				case -1:
					$ret['msg'] = 'rota de webservice não indicada';
					break;
				case -2:
					$ret['msg'] = 'rota de webservice inválida';
					break;
				case -3:
					$ret['msg'] = 'rota de webservice não localizada';
					break;
				case -4:
					$ret['msg'] = 'chave de validação incorreta ou falta de variável essencial';
					break;
				case -10:
					$ret['msg'] = 'rota inválida';
					break;
				case -11:
					$ret['msg'] = 'erro no acesso ao webservice';
					break;
				case -12:
					$ret['msg'] = 'resposta do webservice corrompida';
					break;
				case -13:
					$ret['msg'] = 'resposta do webservice corrompida (falta "e")';
					break;
				default:
					$ret['msg'] = 'erro específico do serviço requisitado, consulte o material de referência';
					break;
			}
			return ($ret);
		}
	}
	
	/**
	 * Recupera o log da última requisição.
	 * @return array o log da operação
	 */
	public function recLog()
	{
		return ($this->log);
	}
	
	/**
	 * Recupera o texto original da última resposta recebida.
	 * @return string o texto original da última resposta
	 */
	public function resposta()
	{
		return ($this->original);
	}
	
	/**
	 * Adiciona uma entrada ao log da requisição.
	 * @param string $texto o texto a adicionar
	 */
	private function adLog($texto)
	{
		$this->log[] = date('d/m/Y H:i:s') . ' => ' . $texto;
	}
	
}