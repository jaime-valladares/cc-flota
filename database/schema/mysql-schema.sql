/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `abastecimiento_rutas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `abastecimiento_rutas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `abastecimiento_id` bigint unsigned NOT NULL,
  `ruta_id` bigint unsigned NOT NULL,
  `orden` smallint unsigned NOT NULL,
  `tipo_recorrido` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `factor_recorrido` tinyint unsigned NOT NULL,
  `ruta_nombre_snapshot` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `punto_origen_id` bigint unsigned NOT NULL,
  `punto_destino_id` bigint unsigned NOT NULL,
  `punto_origen_nombre_snapshot` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `punto_destino_nombre_snapshot` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kilometros_base_snapshot` decimal(14,2) NOT NULL,
  `galones_base_snapshot` decimal(14,2) NOT NULL,
  `kilometros_aplicados` decimal(14,2) NOT NULL,
  `galones_aplicados` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `abastecimiento_rutas_punto_origen_id_foreign` (`punto_origen_id`),
  KEY `abastecimiento_rutas_punto_destino_id_foreign` (`punto_destino_id`),
  KEY `abast_rutas_abast_orden_idx` (`abastecimiento_id`,`orden`),
  KEY `abast_rutas_ruta_abast_idx` (`ruta_id`,`abastecimiento_id`),
  CONSTRAINT `abastecimiento_rutas_abastecimiento_id_foreign` FOREIGN KEY (`abastecimiento_id`) REFERENCES `abastecimientos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abastecimiento_rutas_punto_destino_id_foreign` FOREIGN KEY (`punto_destino_id`) REFERENCES `puntos_ruta` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abastecimiento_rutas_punto_origen_id_foreign` FOREIGN KEY (`punto_origen_id`) REFERENCES `puntos_ruta` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abastecimiento_rutas_ruta_id_foreign` FOREIGN KEY (`ruta_id`) REFERENCES `rutas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `abastecimiento_tanques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `abastecimiento_tanques` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `abastecimiento_id` bigint unsigned NOT NULL,
  `tanque_id` bigint unsigned NOT NULL,
  `orden` smallint unsigned NOT NULL,
  `tanque_nombre_snapshot` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacidad_total_snapshot` decimal(14,2) NOT NULL,
  `volumen_minimo_alerta_snapshot` decimal(14,2) NOT NULL,
  `inventario_anterior` decimal(14,2) NOT NULL,
  `galones_retirados` decimal(14,2) NOT NULL,
  `inventario_resultante` decimal(14,2) NOT NULL,
  `quedo_bajo_minimo` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `abast_tanques_abast_tanque_unique` (`abastecimiento_id`,`tanque_id`),
  KEY `abast_tanques_tanque_abast_idx` (`tanque_id`,`abastecimiento_id`),
  KEY `abast_tanques_abast_orden_idx` (`abastecimiento_id`,`orden`),
  CONSTRAINT `abastecimiento_tanques_abastecimiento_id_foreign` FOREIGN KEY (`abastecimiento_id`) REFERENCES `abastecimientos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abastecimiento_tanques_tanque_id_foreign` FOREIGN KEY (`tanque_id`) REFERENCES `tanques` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `abastecimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `abastecimientos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `unidad_id` bigint unsigned NOT NULL,
  `motorista_id` bigint unsigned NOT NULL,
  `abastecimiento_anterior_id` bigint unsigned DEFAULT NULL,
  `registrado_por` bigint unsigned NOT NULL,
  `empresa_nombre_snapshot` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad_placa_snapshot` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad_marca_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad_modelo_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motorista_nombre_snapshot` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `motorista_licencia_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_hora_abastecimiento` datetime NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registrado',
  `modelo_medicion` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lectura_actual` decimal(14,2) NOT NULL,
  `lectura_anterior` decimal(14,2) DEFAULT NULL,
  `diferencia_lectura` decimal(14,2) DEFAULT NULL,
  `kilometraje_actual` decimal(14,2) DEFAULT NULL,
  `kilometraje_anterior` decimal(14,2) DEFAULT NULL,
  `diferencia_kilometraje` decimal(14,2) DEFAULT NULL,
  `horometro_actual` decimal(14,2) DEFAULT NULL,
  `horometro_anterior` decimal(14,2) DEFAULT NULL,
  `diferencia_horometro` decimal(14,2) DEFAULT NULL,
  `volumen_inicial` decimal(14,2) NOT NULL,
  `volumen_cargado` decimal(14,2) NOT NULL,
  `volumen_final` decimal(14,2) NOT NULL,
  `capacidad_cubierta_snapshot` decimal(14,2) NOT NULL,
  `volumen_final_anterior` decimal(14,2) DEFAULT NULL,
  `combustible_consumido_ciclo` decimal(14,2) DEFAULT NULL,
  `combustible_adicional_no_explicado` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tipo_origen` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gasolinera_interna_id` bigint unsigned DEFAULT NULL,
  `gasolinera_externa_id` bigint unsigned DEFAULT NULL,
  `origen_nombre_snapshot` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_galon` decimal(14,4) DEFAULT NULL,
  `total_pagado` decimal(14,2) DEFAULT NULL,
  `moneda` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_rutas` int unsigned NOT NULL DEFAULT '0',
  `kilometros_teoricos` decimal(14,2) DEFAULT NULL,
  `galones_teoricos` decimal(14,2) DEFAULT NULL,
  `galones_por_kilometro` decimal(18,6) DEFAULT NULL,
  `kilometros_por_galon` decimal(18,6) DEFAULT NULL,
  `galones_por_hora` decimal(18,6) DEFAULT NULL,
  `diferencia_kilometros_teoricos` decimal(14,2) DEFAULT NULL,
  `diferencia_galones_teoricos` decimal(14,2) DEFAULT NULL,
  `total_tapones_abiertos` tinyint unsigned NOT NULL DEFAULT '0',
  `total_marchamos_reemplazados` tinyint unsigned NOT NULL DEFAULT '0',
  `fecha_anulacion` datetime DEFAULT NULL,
  `anulado_por` bigint unsigned DEFAULT NULL,
  `motivo_anulacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `abastecimientos_registrado_por_foreign` (`registrado_por`),
  KEY `abastecimientos_gasolinera_interna_id_foreign` (`gasolinera_interna_id`),
  KEY `abastecimientos_gasolinera_externa_id_foreign` (`gasolinera_externa_id`),
  KEY `abastecimientos_anulado_por_foreign` (`anulado_por`),
  KEY `abastecimientos_anterior_fk` (`abastecimiento_anterior_id`),
  KEY `abast_empresa_fecha_idx` (`empresa_id`,`fecha_hora_abastecimiento`),
  KEY `abast_unidad_estado_fecha_idx` (`unidad_id`,`estado`,`fecha_hora_abastecimiento`),
  KEY `abast_motorista_fecha_idx` (`motorista_id`,`fecha_hora_abastecimiento`),
  KEY `abast_origen_fecha_idx` (`tipo_origen`,`fecha_hora_abastecimiento`),
  KEY `abast_unidad_kilometraje_idx` (`unidad_id`,`kilometraje_actual`),
  KEY `abast_unidad_horometro_idx` (`unidad_id`,`horometro_actual`),
  CONSTRAINT `abastecimientos_anterior_fk` FOREIGN KEY (`abastecimiento_anterior_id`) REFERENCES `abastecimientos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abastecimientos_anulado_por_foreign` FOREIGN KEY (`anulado_por`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abastecimientos_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abastecimientos_gasolinera_externa_id_foreign` FOREIGN KEY (`gasolinera_externa_id`) REFERENCES `gasolineras_externas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abastecimientos_gasolinera_interna_id_foreign` FOREIGN KEY (`gasolinera_interna_id`) REFERENCES `gasolineras` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abastecimientos_motorista_id_foreign` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abastecimientos_registrado_por_foreign` FOREIGN KEY (`registrado_por`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abastecimientos_unidad_id_foreign` FOREIGN KEY (`unidad_id`) REFERENCES `unidades` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `empresas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_legal` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_comercial` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nit` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono_empresa` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo_empresa` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poc_nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poc_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poc_telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` bigint unsigned DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresas_nit_unique` (`nit`),
  KEY `empresas_creado_por_foreign` (`creado_por`),
  KEY `empresas_actualizado_por_foreign` (`actualizado_por`),
  KEY `empresas_inactivado_por_foreign` (`inactivado_por`),
  KEY `empresas_nombre_legal_index` (`nombre_legal`),
  KEY `empresas_nombre_comercial_index` (`nombre_comercial`),
  KEY `empresas_estado_index` (`estado`),
  CONSTRAINT `empresas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `empresas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `empresas_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gasolineras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gasolineras` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `encargado` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` bigint unsigned DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gasolineras_empresa_id_nombre_unique` (`empresa_id`,`nombre`),
  KEY `gasolineras_creado_por_foreign` (`creado_por`),
  KEY `gasolineras_actualizado_por_foreign` (`actualizado_por`),
  KEY `gasolineras_inactivado_por_foreign` (`inactivado_por`),
  KEY `gasolineras_empresa_id_index` (`empresa_id`),
  KEY `gasolineras_nombre_index` (`nombre`),
  KEY `gasolineras_estado_index` (`estado`),
  KEY `gasolineras_empresa_id_estado_index` (`empresa_id`,`estado`),
  CONSTRAINT `gasolineras_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gasolineras_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gasolineras_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gasolineras_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gasolineras_externas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gasolineras_externas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `compania` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` bigint unsigned DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gas_ext_empresa_compania_direccion_unique` (`empresa_id`,`compania`,`direccion`),
  KEY `gasolineras_externas_creado_por_foreign` (`creado_por`),
  KEY `gasolineras_externas_actualizado_por_foreign` (`actualizado_por`),
  KEY `gasolineras_externas_inactivado_por_foreign` (`inactivado_por`),
  KEY `gasolineras_externas_empresa_id_index` (`empresa_id`),
  KEY `gasolineras_externas_estado_index` (`estado`),
  KEY `gasolineras_externas_compania_index` (`compania`),
  KEY `gasolineras_externas_empresa_id_estado_index` (`empresa_id`,`estado`),
  CONSTRAINT `gasolineras_externas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gasolineras_externas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gasolineras_externas_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `gasolineras_externas_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `licencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `licencias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `unidad_id` bigint unsigned NOT NULL,
  `periodo_vigencia_meses` tinyint unsigned NOT NULL,
  `fecha_activacion` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('activa','inactiva') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `plantilla_puntos_seguridad` enum('plantilla_1_tanque','plantilla_2_tanques','plantilla_3_tanques') COLLATE utf8mb4_unicode_ci NOT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `licencias_unidad_id_unique` (`unidad_id`),
  KEY `licencias_creado_por_foreign` (`creado_por`),
  KEY `licencias_actualizado_por_foreign` (`actualizado_por`),
  KEY `licencias_inactivado_por_foreign` (`inactivado_por`),
  KEY `licencias_empresa_id_index` (`empresa_id`),
  KEY `licencias_estado_index` (`estado`),
  KEY `licencias_periodo_vigencia_meses_index` (`periodo_vigencia_meses`),
  KEY `licencias_fecha_activacion_index` (`fecha_activacion`),
  KEY `licencias_fecha_vencimiento_index` (`fecha_vencimiento`),
  KEY `licencias_plantilla_puntos_seguridad_index` (`plantilla_puntos_seguridad`),
  CONSTRAINT `licencias_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `licencias_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `licencias_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `licencias_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `licencias_unidad_id_foreign` FOREIGN KEY (`unidad_id`) REFERENCES `unidades` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `marchamos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marchamos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `unidad_id` bigint unsigned NOT NULL,
  `punto_seguridad_id` bigint unsigned NOT NULL,
  `codigo_marchamo` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_activacion` timestamp NOT NULL,
  `estado` enum('activo','reemplazado','anulado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `activo_actual` tinyint unsigned DEFAULT '1',
  `fecha_desactivacion` timestamp NULL DEFAULT NULL,
  `motivo_desactivacion` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origen_creacion` enum('asignacion_inicial','abastecimiento','reemplazo_dano_desgaste','correccion') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'asignacion_inicial',
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `marchamos_codigo_marchamo_unique` (`codigo_marchamo`),
  KEY `marchamos_creado_por_foreign` (`creado_por`),
  KEY `marchamos_actualizado_por_foreign` (`actualizado_por`),
  KEY `marchamos_empresa_id_index` (`empresa_id`),
  KEY `marchamos_unidad_id_index` (`unidad_id`),
  KEY `marchamos_punto_seguridad_id_index` (`punto_seguridad_id`),
  KEY `marchamos_codigo_marchamo_index` (`codigo_marchamo`),
  KEY `marchamos_estado_index` (`estado`),
  KEY `marchamos_activo_actual_index` (`activo_actual`),
  KEY `marchamos_fecha_activacion_index` (`fecha_activacion`),
  KEY `marchamos_origen_creacion_index` (`origen_creacion`),
  KEY `marchamos_punto_activo_index` (`punto_seguridad_id`,`activo_actual`),
  CONSTRAINT `marchamos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marchamos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marchamos_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `marchamos_punto_seguridad_id_foreign` FOREIGN KEY (`punto_seguridad_id`) REFERENCES `puntos_seguridad_unidad` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `marchamos_unidad_id_foreign` FOREIGN KEY (`unidad_id`) REFERENCES `unidades` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `motoristas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motoristas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `nombres` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `licencia` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` bigint unsigned DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `motoristas_empresa_id_licencia_unique` (`empresa_id`,`licencia`),
  KEY `motoristas_creado_por_foreign` (`creado_por`),
  KEY `motoristas_actualizado_por_foreign` (`actualizado_por`),
  KEY `motoristas_inactivado_por_foreign` (`inactivado_por`),
  KEY `motoristas_empresa_id_index` (`empresa_id`),
  KEY `motoristas_nombres_index` (`nombres`),
  KEY `motoristas_apellidos_index` (`apellidos`),
  KEY `motoristas_licencia_index` (`licencia`),
  KEY `motoristas_estado_index` (`estado`),
  KEY `motoristas_empresa_id_estado_index` (`empresa_id`,`estado`),
  CONSTRAINT `motoristas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `motoristas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `motoristas_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `motoristas_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos_inventario_combustible`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_inventario_combustible` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `tanque_id` bigint unsigned NOT NULL,
  `abastecimiento_id` bigint unsigned DEFAULT NULL,
  `recarga_combustible_id` bigint unsigned DEFAULT NULL,
  `tipo_movimiento` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `volumen_anterior` decimal(10,2) NOT NULL,
  `sentido_movimiento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `volumen_movimiento` decimal(10,2) NOT NULL,
  `volumen_resultante` decimal(10,2) NOT NULL,
  `subtotal_compra` decimal(14,2) DEFAULT NULL,
  `fecha_hora_movimiento` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `usuario_registra_id` bigint unsigned NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registrado',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_anulacion` timestamp NULL DEFAULT NULL,
  `anulado_por` bigint unsigned DEFAULT NULL,
  `motivo_anulacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_inventario_combustible_usuario_registra_id_foreign` (`usuario_registra_id`),
  KEY `movimientos_inventario_combustible_actualizado_por_foreign` (`actualizado_por`),
  KEY `movimientos_inventario_combustible_anulado_por_foreign` (`anulado_por`),
  KEY `movimientos_inventario_combustible_empresa_id_index` (`empresa_id`),
  KEY `movimientos_inventario_combustible_tanque_id_index` (`tanque_id`),
  KEY `movimientos_inventario_combustible_abastecimiento_id_index` (`abastecimiento_id`),
  KEY `movimientos_inventario_combustible_tipo_movimiento_index` (`tipo_movimiento`),
  KEY `movimientos_inventario_combustible_sentido_movimiento_index` (`sentido_movimiento`),
  KEY `movimientos_inventario_combustible_fecha_hora_movimiento_index` (`fecha_hora_movimiento`),
  KEY `movimientos_inventario_combustible_estado_index` (`estado`),
  KEY `mic_empresa_estado_idx` (`empresa_id`,`estado`),
  KEY `mic_recarga_idx` (`recarga_combustible_id`),
  CONSTRAINT `mic_recarga_fk` FOREIGN KEY (`recarga_combustible_id`) REFERENCES `recargas_combustible` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_inventario_combustible_abastecimiento_id_foreign` FOREIGN KEY (`abastecimiento_id`) REFERENCES `abastecimientos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `movimientos_inventario_combustible_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_inventario_combustible_anulado_por_foreign` FOREIGN KEY (`anulado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_inventario_combustible_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_inventario_combustible_tanque_id_foreign` FOREIGN KEY (`tanque_id`) REFERENCES `tanques` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_inventario_combustible_usuario_registra_id_foreign` FOREIGN KEY (`usuario_registra_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permisos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accion` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `alcance` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` bigint unsigned DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permisos_codigo_unique` (`codigo`),
  KEY `permisos_creado_por_foreign` (`creado_por`),
  KEY `permisos_actualizado_por_foreign` (`actualizado_por`),
  KEY `permisos_inactivado_por_foreign` (`inactivado_por`),
  KEY `permisos_codigo_index` (`codigo`),
  KEY `permisos_modulo_index` (`modulo`),
  KEY `permisos_accion_index` (`accion`),
  KEY `permisos_alcance_index` (`alcance`),
  KEY `permisos_estado_index` (`estado`),
  CONSTRAINT `permisos_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `permisos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `permisos_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `puntos_ruta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `puntos_ruta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` bigint unsigned DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `puntos_ruta_empresa_id_nombre_unique` (`empresa_id`,`nombre`),
  KEY `puntos_ruta_creado_por_foreign` (`creado_por`),
  KEY `puntos_ruta_actualizado_por_foreign` (`actualizado_por`),
  KEY `puntos_ruta_inactivado_por_foreign` (`inactivado_por`),
  KEY `puntos_ruta_empresa_id_index` (`empresa_id`),
  KEY `puntos_ruta_nombre_index` (`nombre`),
  KEY `puntos_ruta_estado_index` (`estado`),
  KEY `puntos_ruta_empresa_id_estado_index` (`empresa_id`,`estado`),
  CONSTRAINT `puntos_ruta_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `puntos_ruta_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `puntos_ruta_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `puntos_ruta_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `puntos_seguridad_unidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `puntos_seguridad_unidad` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `unidad_id` bigint unsigned NOT NULL,
  `orden` smallint unsigned NOT NULL,
  `codigo_punto` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grupo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subgrupo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_punto` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `posicion_tanque` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_punto` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requiere_marchamo` tinyint(1) NOT NULL DEFAULT '1',
  `plantilla_origen` enum('plantilla_1_tanque','plantilla_2_tanques','plantilla_3_tanques') COLLATE utf8mb4_unicode_ci NOT NULL,
  `criterio_origen` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_asignacion` enum('pendiente','asignado','corregido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `marchamo_actual_id` bigint unsigned DEFAULT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `puntos_seguridad_unidad_unidad_id_orden_unique` (`unidad_id`,`orden`),
  KEY `puntos_seguridad_unidad_creado_por_foreign` (`creado_por`),
  KEY `puntos_seguridad_unidad_actualizado_por_foreign` (`actualizado_por`),
  KEY `puntos_seguridad_unidad_inactivado_por_foreign` (`inactivado_por`),
  KEY `puntos_seguridad_unidad_unidad_id_index` (`unidad_id`),
  KEY `puntos_seguridad_unidad_estado_index` (`estado`),
  KEY `puntos_seguridad_unidad_estado_asignacion_index` (`estado_asignacion`),
  KEY `puntos_seguridad_unidad_plantilla_origen_index` (`plantilla_origen`),
  KEY `puntos_seguridad_unidad_marchamo_actual_id_index` (`marchamo_actual_id`),
  CONSTRAINT `puntos_seguridad_unidad_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `puntos_seguridad_unidad_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `puntos_seguridad_unidad_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `puntos_seguridad_unidad_marchamo_actual_id_foreign` FOREIGN KEY (`marchamo_actual_id`) REFERENCES `marchamos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `puntos_seguridad_unidad_unidad_id_foreign` FOREIGN KEY (`unidad_id`) REFERENCES `unidades` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recargas_combustible`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recargas_combustible` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `gasolinera_id` bigint unsigned NOT NULL,
  `precio_galon` decimal(10,4) NOT NULL,
  `total_galones` decimal(12,2) NOT NULL,
  `total_compra` decimal(14,2) NOT NULL,
  `fecha_hora_recarga` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `usuario_registra_id` bigint unsigned NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registrado',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_anulacion` timestamp NULL DEFAULT NULL,
  `anulado_por` bigint unsigned DEFAULT NULL,
  `motivo_anulacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recargas_combustible_usuario_registra_id_foreign` (`usuario_registra_id`),
  KEY `recargas_combustible_actualizado_por_foreign` (`actualizado_por`),
  KEY `recargas_combustible_anulado_por_foreign` (`anulado_por`),
  KEY `recargas_combustible_empresa_id_index` (`empresa_id`),
  KEY `recargas_combustible_gasolinera_id_index` (`gasolinera_id`),
  KEY `recargas_combustible_fecha_hora_recarga_index` (`fecha_hora_recarga`),
  KEY `recargas_combustible_estado_index` (`estado`),
  KEY `rc_empresa_estado_idx` (`empresa_id`,`estado`),
  KEY `rc_gasolinera_estado_idx` (`gasolinera_id`,`estado`),
  CONSTRAINT `recargas_combustible_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recargas_combustible_anulado_por_foreign` FOREIGN KEY (`anulado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recargas_combustible_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recargas_combustible_gasolinera_id_foreign` FOREIGN KEY (`gasolinera_id`) REFERENCES `gasolineras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recargas_combustible_usuario_registra_id_foreign` FOREIGN KEY (`usuario_registra_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reemplazo_marchamos_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reemplazo_marchamos_detalle` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reemplazo_evento_id` bigint unsigned NOT NULL,
  `punto_seguridad_id` bigint unsigned NOT NULL,
  `marchamo_anterior_id` bigint unsigned NOT NULL,
  `marchamo_nuevo_id` bigint unsigned NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reemplazo_detalle_evento_punto_unique` (`reemplazo_evento_id`,`punto_seguridad_id`),
  UNIQUE KEY `reemplazo_detalle_marchamo_anterior_unique` (`marchamo_anterior_id`),
  UNIQUE KEY `reemplazo_detalle_marchamo_nuevo_unique` (`marchamo_nuevo_id`),
  KEY `reemplazo_marchamos_detalle_reemplazo_evento_id_index` (`reemplazo_evento_id`),
  KEY `reemplazo_marchamos_detalle_punto_seguridad_id_index` (`punto_seguridad_id`),
  KEY `reemplazo_marchamos_detalle_marchamo_anterior_id_index` (`marchamo_anterior_id`),
  KEY `reemplazo_marchamos_detalle_marchamo_nuevo_id_index` (`marchamo_nuevo_id`),
  KEY `reemplazo_marchamos_detalle_fecha_registro_index` (`fecha_registro`),
  CONSTRAINT `reemplazo_marchamos_detalle_marchamo_anterior_id_foreign` FOREIGN KEY (`marchamo_anterior_id`) REFERENCES `marchamos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `reemplazo_marchamos_detalle_marchamo_nuevo_id_foreign` FOREIGN KEY (`marchamo_nuevo_id`) REFERENCES `marchamos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `reemplazo_marchamos_detalle_punto_seguridad_id_foreign` FOREIGN KEY (`punto_seguridad_id`) REFERENCES `puntos_seguridad_unidad` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `reemplazo_marchamos_detalle_reemplazo_evento_id_foreign` FOREIGN KEY (`reemplazo_evento_id`) REFERENCES `reemplazo_marchamos_eventos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reemplazo_marchamos_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reemplazo_marchamos_eventos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `unidad_id` bigint unsigned NOT NULL,
  `abastecimiento_id` bigint unsigned DEFAULT NULL,
  `motivo_reemplazo` enum('dano','desgaste','perdida','manipulacion_detectada','correccion_instalacion','apertura_abastecimiento') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad_reemplazos` smallint unsigned NOT NULL DEFAULT '0',
  `origen_evento` enum('reemplazo_general','abastecimiento') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reemplazo_general',
  `estado` enum('registrado','anulado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registrado',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` bigint unsigned DEFAULT NULL,
  `fecha_anulacion` timestamp NULL DEFAULT NULL,
  `anulado_por` bigint unsigned DEFAULT NULL,
  `motivo_anulacion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reemplazo_evento_abastecimiento_unique` (`abastecimiento_id`),
  KEY `reemplazo_marchamos_eventos_anulado_por_foreign` (`anulado_por`),
  KEY `reemplazo_marchamos_eventos_empresa_id_index` (`empresa_id`),
  KEY `reemplazo_marchamos_eventos_unidad_id_index` (`unidad_id`),
  KEY `reemplazo_marchamos_eventos_motivo_reemplazo_index` (`motivo_reemplazo`),
  KEY `reemplazo_marchamos_eventos_origen_evento_index` (`origen_evento`),
  KEY `reemplazo_marchamos_eventos_estado_index` (`estado`),
  KEY `reemplazo_marchamos_eventos_fecha_registro_index` (`fecha_registro`),
  KEY `reemplazo_marchamos_eventos_registrado_por_index` (`registrado_por`),
  CONSTRAINT `reemplazo_marchamos_eventos_abastecimiento_id_foreign` FOREIGN KEY (`abastecimiento_id`) REFERENCES `abastecimientos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `reemplazo_marchamos_eventos_anulado_por_foreign` FOREIGN KEY (`anulado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reemplazo_marchamos_eventos_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `reemplazo_marchamos_eventos_registrado_por_foreign` FOREIGN KEY (`registrado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reemplazo_marchamos_eventos_unidad_id_foreign` FOREIGN KEY (`unidad_id`) REFERENCES `unidades` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rol_permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol_permisos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rol_id` bigint unsigned NOT NULL,
  `permiso_id` bigint unsigned NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rol_permisos_rol_id_permiso_id_unique` (`rol_id`,`permiso_id`),
  KEY `rol_permisos_creado_por_foreign` (`creado_por`),
  KEY `rol_permisos_rol_id_index` (`rol_id`),
  KEY `rol_permisos_permiso_id_index` (`permiso_id`),
  CONSTRAINT `rol_permisos_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rol_permisos_permiso_id_foreign` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rol_permisos_rol_id_foreign` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alcance` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` bigint unsigned DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_codigo_unique` (`codigo`),
  KEY `roles_creado_por_foreign` (`creado_por`),
  KEY `roles_actualizado_por_foreign` (`actualizado_por`),
  KEY `roles_inactivado_por_foreign` (`inactivado_por`),
  KEY `roles_codigo_index` (`codigo`),
  KEY `roles_alcance_index` (`alcance`),
  KEY `roles_estado_index` (`estado`),
  CONSTRAINT `roles_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `roles_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `roles_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rutas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rutas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `punto_origen_id` bigint unsigned NOT NULL,
  `punto_destino_id` bigint unsigned NOT NULL,
  `ruta` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kilometros_estimados` decimal(10,2) NOT NULL,
  `galones_estimados` decimal(10,2) NOT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` bigint unsigned DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` datetime DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rutas_empresa_origen_destino_unique` (`empresa_id`,`punto_origen_id`,`punto_destino_id`),
  KEY `rutas_punto_origen_id_foreign` (`punto_origen_id`),
  KEY `rutas_punto_destino_id_foreign` (`punto_destino_id`),
  KEY `rutas_creado_por_foreign` (`creado_por`),
  KEY `rutas_actualizado_por_foreign` (`actualizado_por`),
  KEY `rutas_inactivado_por_foreign` (`inactivado_por`),
  KEY `rutas_estado_index` (`estado`),
  CONSTRAINT `rutas_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rutas_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rutas_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `rutas_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rutas_punto_destino_id_foreign` FOREIGN KEY (`punto_destino_id`) REFERENCES `puntos_ruta` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `rutas_punto_origen_id_foreign` FOREIGN KEY (`punto_origen_id`) REFERENCES `puntos_ruta` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tanques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tanques` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gasolinera_id` bigint unsigned NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacidad_total` decimal(10,2) NOT NULL,
  `volumen_actual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `volumen_minimo_alerta` decimal(10,2) NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` bigint unsigned DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tanques_gasolinera_id_nombre_unique` (`gasolinera_id`,`nombre`),
  KEY `tanques_creado_por_foreign` (`creado_por`),
  KEY `tanques_actualizado_por_foreign` (`actualizado_por`),
  KEY `tanques_inactivado_por_foreign` (`inactivado_por`),
  KEY `tanques_gasolinera_id_index` (`gasolinera_id`),
  KEY `tanques_estado_index` (`estado`),
  KEY `tanques_gasolinera_id_estado_index` (`gasolinera_id`,`estado`),
  KEY `tanques_volumen_actual_volumen_minimo_alerta_index` (`volumen_actual`,`volumen_minimo_alerta`),
  CONSTRAINT `tanques_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tanques_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tanques_gasolinera_id_foreign` FOREIGN KEY (`gasolinera_id`) REFERENCES `gasolineras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tanques_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unidades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `placa` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_tanques` tinyint unsigned NOT NULL,
  `cantidad_tanques_con_licencia` tinyint unsigned NOT NULL,
  `capacidad_total` decimal(10,2) NOT NULL,
  `capacidad_cubierta` decimal(10,2) NOT NULL,
  `modelo_medicion` enum('galones_hora','kilometros_galon','galones_viaje') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('registrada','activa','inactiva') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registrada',
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unidades_empresa_id_placa_unique` (`empresa_id`,`placa`),
  KEY `unidades_creado_por_foreign` (`creado_por`),
  KEY `unidades_actualizado_por_foreign` (`actualizado_por`),
  KEY `unidades_inactivado_por_foreign` (`inactivado_por`),
  KEY `unidades_empresa_id_index` (`empresa_id`),
  KEY `unidades_estado_index` (`estado`),
  KEY `unidades_modelo_medicion_index` (`modelo_medicion`),
  CONSTRAINT `unidades_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `unidades_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `unidades_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `unidades_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned DEFAULT NULL,
  `rol_id` bigint unsigned DEFAULT NULL,
  `tipo_usuario` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `es_cuenta_recuperacion` tinyint(1) NOT NULL DEFAULT '0',
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `fecha_inactivacion` timestamp NULL DEFAULT NULL,
  `inactivado_por` bigint unsigned DEFAULT NULL,
  `motivo_inactivacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_por` bigint unsigned DEFAULT NULL,
  `actualizado_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_inactivado_por_foreign` (`inactivado_por`),
  KEY `users_empresa_id_index` (`empresa_id`),
  KEY `users_rol_id_index` (`rol_id`),
  KEY `users_tipo_usuario_index` (`tipo_usuario`),
  KEY `users_estado_index` (`estado`),
  KEY `users_creado_por_index` (`creado_por`),
  KEY `users_actualizado_por_index` (`actualizado_por`),
  KEY `users_ultimo_acceso_index` (`ultimo_acceso`),
  KEY `users_es_cuenta_recuperacion_index` (`es_cuenta_recuperacion`),
  CONSTRAINT `users_actualizado_por_foreign` FOREIGN KEY (`actualizado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_inactivado_por_foreign` FOREIGN KEY (`inactivado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_rol_id_foreign` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_06_08_230235_create_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_06_08_231948_create_permisos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_06_08_232859_create_rol_permisos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_06_08_234000_create_empresas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_06_08_234509_add_cc_flota_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_06_11_180351_add_audit_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_06_12_202746_create_unidads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_06_20_023635_create_licencias_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_06_20_034832_remove_observaciones_from_licencias_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_06_20_182712_create_puntos_seguridad_unidad_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_06_20_182753_create_marchamos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_06_20_182820_add_marchamo_actual_fk_to_puntos_seguridad_unidad_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_06_23_035630_create_reemplazo_marchamos_eventos_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_06_23_035641_create_reemplazo_marchamos_detalle_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_06_23_040925_ajustar_indices_marchamos_para_reemplazos',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_07_03_221820_create_gasolineras_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_07_03_221829_create_tanques_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_07_03_221839_create_movimientos_inventario_combustible_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_07_07_200355_create_gasolineras_externas_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_07_07_211303_create_puntos_ruta_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_07_07_220500_create_motoristas_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_07_09_035421_create_recargas_combustible_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_07_09_035422_add_recarga_fields_to_movimientos_inventario_combustible_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_07_09_051047_simplificar_gasolineras_externas_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_07_09_154543_add_direccion_to_puntos_ruta_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_07_09_180425_fix_unique_index_on_gasolineras_externas_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_07_09_180706_corregir_indice_gasolineras_externas',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_07_10_131049_create_rutas_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_07_13_090000_drop_inactivado_por_gasolinera_from_tanques_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_07_15_183937_create_abastecimientos_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_07_15_184050_create_abastecimiento_tanques_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_07_15_190214_create_abastecimiento_rutas_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_07_15_190912_correct_unidad_foreign_key_on_abastecimientos_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_07_15_212329_add_abastecimiento_support_to_reemplazo_marchamos_eventos_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_07_15_213445_add_abastecimiento_foreign_key_to_movimientos_inventario_combustible_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_07_15_215058_make_codigo_marchamo_globally_unique_on_marchamos_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_07_16_170156_complete_abastecimientos_table_structure',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_07_16_182205_complete_abastecimiento_tanques_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_07_16_183017_complete_abastecimiento_rutas_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_07_16_203129_add_kilometraje_and_horometro_to_abastecimientos_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_07_24_000001_add_es_cuenta_recuperacion_to_users_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_08_25_000001_migrate_modelo_medicion_to_kilometros_galon',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_08_26_000001_make_nombre_placa_unico_por_empresa',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_08_27_000001_restore_abastecimiento_foreign_key_on_movimientos_inventario_combustible_table',30);
