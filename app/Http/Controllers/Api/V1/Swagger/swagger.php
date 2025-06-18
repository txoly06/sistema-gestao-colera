<?php

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API de Gestão e Monitoramento de Casos de Cólera",
 *     description="Documentação da API para Sistema de Gestão e Monitoramento de Casos de Cólera em Angola",
 *     @OA\Contact(
 *         email="admin@saudeangola.gov",
 *         name="Suporte Técnico"
 *     ),
 *     @OA\License(
 *         name="Licença MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 */

/**
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Versão 1"
 * )
 */

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

/**
 * @OA\Tag(
 *     name="Autenticação",
 *     description="Endpoints para autenticação de usuários"
 * )
 * @OA\Tag(
 *     name="Pacientes",
 *     description="Operações relacionadas a pacientes"
 * )
 * @OA\Tag(
 *     name="Triagens",
 *     description="Operações relacionadas a triagens e avaliações de risco"
 * )
 * @OA\Tag(
 *     name="Pontos de Cuidado",
 *     description="Gestão de pontos de cuidado de emergência"
 * )
 * @OA\Tag(
 *     name="Veículos",
 *     description="Gestão da frota de veículos de emergência"
 * )
 * @OA\Tag(
 *     name="Gabinetes",
 *     description="Gestão de gabinetes provinciais"
 * )
 * @OA\Tag(
 *     name="Unidades de Saúde",
 *     description="Gestão de unidades de saúde"
 * )
 * @OA\Tag(
 *     name="Mapas",
 *     description="Endpoints relacionados à geolocalização"
 * )
 * @OA\Tag(
 *     name="Relatórios",
 *     description="Endpoints para geração de relatórios"
 * )
 * @OA\Tag(
 *     name="Auditoria",
 *     description="Logs de auditoria e rastreamento de atividades"
 * )
 */
