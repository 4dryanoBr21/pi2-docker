<?php
// Textos del sistema en español.
// Clave => texto mostrado. Al agregar un texto nuevo en alguna página,
// agregá la clave acá y también en lang/pt.php.
return [
    // Errores y mensajes genéricos (reutilizados en varias páginas)
    'erro_sessao_expirada' => 'Sesión expirada. Recargá la página e intentá de nuevo.',
    'erro_interno_tente_novamente' => 'Error interno. Intentá de nuevo.',

    // index.php (pantalla "Entrar a la Sala")
    'erro_preencha_codigo' => '¡Completá el código de la sala!',
    'erro_preencha_nome' => '¡Completá tu nombre!',
    'erro_nome_longo' => 'Nombre demasiado largo (máximo 100 caracteres).',
    'erro_inserir_participante' => 'Error al agregar participante.',
    'erro_codigo_invalido' => 'Código de sala inválido.',
    'erro_preparar_consulta' => 'Error al preparar la consulta SQL.',
    'titulo_entrar_sala' => 'Entrar a la Sala',
    'label_codigo_sala' => 'Código de la Sala',
    'label_nome_convidado' => 'Nombre del Invitado',
    'btn_entrar' => 'Entrar',
    'btn_criar_sala' => 'Crear Sala',
    'alt_logo' => 'Logo de ME INSCREVO',

    // login.php
    'erro_preencha_email_usuario' => '¡Completá tu e-mail o nombre de usuario!',
    'erro_preencha_senha' => '¡Completá tu contraseña!',
    'erro_conta_logada' => 'Esta cuenta ya está conectada en otro dispositivo/pestaña. Cerrá sesión ahí primero, o esperá unos minutos de inactividad e intentá de nuevo.',
    'erro_registrar_sessao' => 'Error interno al registrar la sesión.',
    'erro_usuario_senha_incorretos' => '¡Usuario o contraseña incorrectos!',
    'erro_servidor_bd' => 'Error interno en el servidor de base de datos.',
    'title_login' => 'ME INSCREVO - Iniciar sesión',
    'titulo_login' => 'Iniciar sesión',
    'label_usuario_email' => 'Nombre de usuario o e-mail',
    'label_senha' => 'Contraseña',
    'btn_registrar' => 'Registrarse',
    'aria_sair_sem_entrar' => 'Salir sin entrar',

    // register.php
    'erro_preencha_campos' => 'Completá todos los campos.',
    'erro_email_invalido' => 'Ingresá un e-mail válido.',
    'erro_senha_minima' => 'La contraseña debe tener al menos 8 caracteres.',
    'erro_usuario_existente' => 'Usuario o e-mail ya registrado en el sistema.',
    'msg_cadastro_sucesso' => 'Usuario registrado con éxito. Hacé clic <a href="login.php" class="alert-link">%s</a> para continuar',
    'msg_cadastro_sucesso_link' => 'acá',
    'erro_cadastro_bd' => 'Error al registrar en la base de datos.',
    'erro_servidor_dados' => 'Error interno en el servidor de datos.',
    'title_register' => 'ME INSCREVO - Registrarse',
    'titulo_register' => 'Registrarse',
    'label_username' => 'Nombre de usuario',
    'label_email' => 'E-mail',
    'label_password' => 'Contraseña',
    'texto_min_caracteres' => 'Mínimo 8 caracteres.',
    'aria_sair_sem_registrar' => 'Salir sin registrarse',

    // criar.php
    'erro_preencha_todos_campos' => 'Por favor completá todos los campos.',
    'erro_codigo_formato' => 'El código de la sala debe tener de 4 a 20 caracteres, solo letras y números.',
    'erro_sala_existente' => 'La sala ya existe.',
    'erro_preparar_verificacao' => 'Error al preparar la consulta de verificación.',
    'erro_codigo_em_uso' => 'Ese código de sala ya está en uso. Elegí otro.',
    'erro_criar_sala' => 'Error al crear la sala. Intentá de nuevo.',
    'title_criar_sala' => 'ME INSCREVO - Crear Sala',
    'titulo_criar_sala' => 'Crear Sala',
    'aria_sair_conta' => 'Cerrar sesión',
    'label_nome_sala' => 'Nombre de la Sala',
    'btn_copiar_codigo' => 'Copiar código',
    'texto_copiado' => '¡Copiado!',
    'label_tempo_fala' => 'Tiempo de habla de los participantes (horas:minutos:segundos)',
    'btn_criar' => 'Crear',

    // criador.php
    'erro_sala_nao_especificada' => 'Sala no especificada. <a href="criar.php">%s</a>',
    'erro_sem_permissao' => 'No tenés permiso para acceder a esta sala. <a href="criar.php">%s</a>',
    'erro_sala_nao_encontrada' => 'Sala no encontrada. <a href="criar.php">%s</a>',
    'texto_voltar' => 'Volver',
    'btn_sair' => 'Salir',
    'aria_encerrar_sala' => 'Cerrar la sala y eliminar a todos los participantes',
    'label_codigo_prefixo' => 'Código:',
    'texto_tempo_reuniao' => 'Tiempo de reunión:',
    'texto_tempo_fala_participante' => 'Tiempo de habla por participante:',
    'texto_nenhum_falando' => 'Nadie está hablando en este momento.',
    'btn_iniciar_proximo' => 'Dar la palabra al siguiente',
    'js_passar_vez' => 'Pasar el turno',
    'titulo_participantes_presentes' => 'Participantes presentes',
    'confirm_encerrar_sala' => '¿Seguro que querés cerrar la sala? Esto va a borrar la sala y eliminar a todos los participantes — no se puede deshacer.',
    'erro_fechar_sala' => 'Error al cerrar la sala.',
    'texto_nenhum_participante_ainda' => 'Todavía no hay participantes en la sala.',

    // participante.php
    'erro_sala_nao_encontrada_simples' => 'Sala no encontrada.',
    'aria_sair_sala' => 'Salir de la sala',
    'texto_aguardando' => 'Esperando...',
    'texto_levante_mao' => 'Levantá la mano para entrar a la fila.',
    'texto_sua_vez' => '¡Es tu turno de hablar!',
    'aria_levantar_mao' => 'Levantar la mano',
    'aria_abaixar_mao' => 'Bajar la mano',
    'texto_posicao_fila' => 'Sos el %dº de la fila (%d en total).',
    'texto_voce_sufixo' => ' (vos)',
    'erro_sair_sala' => 'Error al salir de la sala.',

    // selector de idioma
    'idioma_pt_titulo' => 'Português (Brasil)',
    'idioma_es_titulo' => 'Español (Argentina)',
];
