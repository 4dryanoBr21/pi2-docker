<?php
// Textos do sistema em português do Brasil.
// Chave => texto exibido. Ao adicionar um texto novo em alguma página,
// adicione a chave aqui e também em lang/es.php.
return [
    // Erros e mensagens genéricas (reaproveitados em várias páginas)
    'erro_sessao_expirada' => 'Sessão expirada. Recarregue a página e tente novamente.',
    'erro_interno_tente_novamente' => 'Erro interno. Tente novamente.',

    // index.php (tela "Entrar na Sala")
    'erro_preencha_codigo' => 'Preencha o código da sala!',
    'erro_preencha_nome' => 'Preencha seu nome!',
    'erro_nome_longo' => 'Nome muito longo (máximo 100 caracteres).',
    'erro_inserir_participante' => 'Erro ao inserir participante.',
    'erro_codigo_invalido' => 'Código de sala inválido.',
    'erro_preparar_consulta' => 'Erro ao preparar consulta SQL.',
    'titulo_entrar_sala' => 'Entrar na Sala',
    'label_codigo_sala' => 'Código da Sala',
    'label_nome_convidado' => 'Nome do Convidado',
    'btn_entrar' => 'Entrar',
    'btn_criar_sala' => 'Criar Sala',
    'alt_logo' => 'Logo do ME INSCREVO',

    // login.php
    'erro_preencha_email_usuario' => 'Preencha seu e-mail ou nome de usuário!',
    'erro_preencha_senha' => 'Preencha sua senha!',
    'erro_conta_logada' => 'Esta conta já está logada em outro dispositivo/aba. Saia de lá primeiro, ou aguarde alguns minutos de inatividade e tente novamente.',
    'erro_registrar_sessao' => 'Erro interno ao registrar sessão.',
    'erro_usuario_senha_incorretos' => 'Usuário ou senha incorretos!',
    'erro_servidor_bd' => 'Erro interno no servidor de banco de dados.',
    'title_login' => 'ME INSCREVO - Login',
    'titulo_login' => 'Login',
    'label_usuario_email' => 'Nome de usuário ou e-mail',
    'label_senha' => 'Senha',
    'btn_registrar' => 'Registrar',
    'aria_sair_sem_entrar' => 'Sair sem entrar',

    // register.php
    'erro_preencha_campos' => 'Preencha todos os campos.',
    'erro_email_invalido' => 'Digite um e-mail válido.',
    'erro_senha_minima' => 'A senha precisa ter pelo menos 8 caracteres.',
    'erro_usuario_existente' => 'Usuário ou E-mail já cadastrado no sistema.',
    'msg_cadastro_sucesso' => 'Usuário cadastrado com sucesso. Clique em <a href="login.php" class="alert-link">%s</a> para continuar',
    'msg_cadastro_sucesso_link' => 'aqui',
    'erro_cadastro_bd' => 'Erro ao realizar o cadastro no banco de dados.',
    'erro_servidor_dados' => 'Erro interno no servidor de dados.',
    'title_register' => 'ME INSCREVO - Registrar',
    'titulo_register' => 'Registrar',
    'label_username' => 'Nome de usuário',
    'label_email' => 'E-mail',
    'label_password' => 'Senha',
    'texto_min_caracteres' => 'Mínimo de 8 caracteres.',
    'aria_sair_sem_registrar' => 'Sair sem registrar',

    // criar.php
    'erro_preencha_todos_campos' => 'Por favor preencha todos os campos.',
    'erro_codigo_formato' => 'O código da sala deve ter de 4 a 20 caracteres, apenas letras e números.',
    'erro_sala_existente' => 'Sala já existente.',
    'erro_preparar_verificacao' => 'Erro ao preparar consulta de verificação.',
    'erro_codigo_em_uso' => 'Esse código de sala já está em uso. Escolha outro.',
    'erro_criar_sala' => 'Erro ao criar sala. Tente novamente.',
    'title_criar_sala' => 'ME INSCREVO - Criar Sala',
    'titulo_criar_sala' => 'Criar Sala',
    'aria_sair_conta' => 'Sair da conta',
    'label_nome_sala' => 'Nome da Sala',
    'btn_copiar_codigo' => 'Copiar código',
    'texto_copiado' => 'Copiado!',
    'label_tempo_fala' => 'Tempo de fala dos participantes (horas:minutos:segundos)',
    'btn_criar' => 'Criar',

    // criador.php
    'erro_sala_nao_especificada' => 'Sala não especificada. <a href="criar.php">%s</a>',
    'erro_sem_permissao' => 'Você não tem permissão para acessar esta sala. <a href="criar.php">%s</a>',
    'erro_sala_nao_encontrada' => 'Sala não encontrada. <a href="criar.php">%s</a>',
    'texto_voltar' => 'Voltar',
    'btn_sair' => 'Sair',
    'aria_encerrar_sala' => 'Encerrar sala e apagar todos os participantes',
    'label_codigo_prefixo' => 'Código:',
    'texto_tempo_reuniao' => 'Tempo de reunião:',
    'texto_tempo_fala_participante' => 'Tempo de fala por participante:',
    'texto_nenhum_falando' => 'Nenhum participante falando no momento.',
    'btn_iniciar_proximo' => 'Iniciar fala do próximo',
    'js_passar_vez' => 'Passar a vez',
    'titulo_participantes_presentes' => 'Participantes presentes',
    'confirm_encerrar_sala' => 'Tem certeza que deseja encerrar a sala? Isso vai apagar a sala e remover todos os participantes — não pode ser desfeito.',
    'erro_fechar_sala' => 'Erro ao fechar a sala.',
    'texto_nenhum_participante_ainda' => 'Nenhum participante na sala ainda.',

    // participante.php
    'erro_sala_nao_encontrada_simples' => 'Sala não encontrada.',
    'aria_sair_sala' => 'Sair da sala',
    'texto_aguardando' => 'Aguardando...',
    'texto_levante_mao' => 'Levante a mão para entrar na fila.',
    'texto_sua_vez' => 'É a sua vez de falar!',
    'aria_levantar_mao' => 'Levantar a mão',
    'aria_abaixar_mao' => 'Abaixar a mão',
    'texto_posicao_fila' => 'Você é o %dº da fila (%d no total).',
    'texto_voce_sufixo' => ' (você)',
    'erro_sair_sala' => 'Erro ao sair da sala.',

    // seletor de idioma
    'idioma_pt_titulo' => 'Português (Brasil)',
    'idioma_es_titulo' => 'Español (Argentina)',
];
