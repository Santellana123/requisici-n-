-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 12-01-2026 a las 05:42:29
-- Versión del servidor: 8.0.30
-- Versión de PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_requisiciones_itsm`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas`
--

CREATE TABLE `areas` (
  `id` int NOT NULL,
  `nombre_programa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsable_area` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `poa_archivos`
--

CREATE TABLE `poa_archivos` (
  `id` int NOT NULL,
  `anio_fiscal` int NOT NULL,
  `nombre_archivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_carga` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activo','historial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  `cargado_por` int DEFAULT NULL COMMENT 'Usuario que subió el POA',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `poa_items`
--

CREATE TABLE `poa_items` (
  `id` int NOT NULL,
  `poa_archivo_id` int NOT NULL,
  `area_id` int NOT NULL,
  `programa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proceso` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `partida_presupuestal` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `concepto` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad_medida` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `cantidad_original` int NOT NULL,
  `cantidad_disponible` int NOT NULL,
  `monto_comprometido` decimal(12,2) DEFAULT '0.00',
  `monto_ejercido` decimal(12,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `presupuesto_area_anual`
--

CREATE TABLE `presupuesto_area_anual` (
  `id` int NOT NULL,
  `area_id` int NOT NULL,
  `anio_fiscal` int NOT NULL,
  `presupuesto_asignado` decimal(15,2) NOT NULL DEFAULT '0.00',
  `presupuesto_comprometido` decimal(15,2) DEFAULT '0.00',
  `presupuesto_ejercido` decimal(15,2) DEFAULT '0.00',
  `presupuesto_disponible` decimal(15,2) GENERATED ALWAYS AS (((`presupuesto_asignado` - `presupuesto_comprometido`) - `presupuesto_ejercido`)) STORED,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Control de presupuesto anual por área';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requisiciones`
--

CREATE TABLE `requisiciones` (
  `id` int NOT NULL,
  `folio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` int NOT NULL,
  `area_id` int NOT NULL,
  `fecha_solicitud` date NOT NULL,
  `motivo_solicitud` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `programa_poa` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proyecto_poa` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones_usuario` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `observaciones_grales` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `observaciones_compras` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado` enum('pendiente','validado_planeacion','aprobado_direccion','rechazado','surtido') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `fecha_validacion_planeacion` datetime DEFAULT NULL,
  `fecha_aprobacion_direccion` datetime DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `validado_por` int DEFAULT NULL,
  `aprobado_por` int DEFAULT NULL,
  `rechazado_por` int DEFAULT NULL,
  `motivo_rechazo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `requisiciones`
--
DELIMITER $$
CREATE TRIGGER `trg_actualizar_presupuesto_aprobado` AFTER UPDATE ON `requisiciones` FOR EACH ROW BEGIN
    DECLARE monto_total DECIMAL(12,2);
    
    IF NEW.estado = 'aprobado_direccion' AND OLD.estado != 'aprobado_direccion' THEN
        SELECT SUM(subtotal) INTO monto_total
        FROM requisicion_detalles
        WHERE requisicion_id = NEW.id;
        
        UPDATE presupuesto_area_anual
        SET presupuesto_ejercido = presupuesto_ejercido + COALESCE(monto_total, 0),
            presupuesto_comprometido = presupuesto_comprometido - COALESCE(monto_total, 0)
        WHERE area_id = NEW.area_id 
          AND anio_fiscal = YEAR(NEW.fecha_solicitud);
          
        UPDATE poa_items pi
        INNER JOIN requisicion_detalles rd ON pi.id = rd.poa_item_id
        SET pi.monto_comprometido = pi.monto_comprometido - rd.subtotal,
            pi.monto_ejercido = pi.monto_ejercido + rd.subtotal
        WHERE rd.requisicion_id = NEW.id;
    END IF;
    
    IF NEW.estado = 'rechazado' AND OLD.estado != 'rechazado' THEN
        SELECT SUM(subtotal) INTO monto_total
        FROM requisicion_detalles
        WHERE requisicion_id = NEW.id;
        
        UPDATE presupuesto_area_anual
        SET presupuesto_comprometido = presupuesto_comprometido - COALESCE(monto_total, 0)
        WHERE area_id = NEW.area_id 
          AND anio_fiscal = YEAR(NEW.fecha_solicitud);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_generar_folio_requisicion` BEFORE INSERT ON `requisiciones` FOR EACH ROW BEGIN
    DECLARE nuevo_numero INT;
    DECLARE anio_actual INT;
    
    IF NEW.folio IS NULL OR NEW.folio = '' THEN
        SET anio_actual = YEAR(NEW.fecha_solicitud);
        
        SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(folio, '-', -1) AS UNSIGNED)), 0) + 1
        INTO nuevo_numero
        FROM requisiciones
        WHERE YEAR(fecha_solicitud) = anio_actual;
        
        SET NEW.folio = CONCAT('REQ-', anio_actual, '-', LPAD(nuevo_numero, 6, '0'));
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_requisicion_cambio_estado` AFTER UPDATE ON `requisiciones` FOR EACH ROW BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO requisicion_historial (
            requisicion_id,
            usuario_id,
            estado_anterior,
            estado_nuevo,
            comentario
        ) VALUES (
            NEW.id,
            COALESCE(NEW.validado_por, NEW.aprobado_por, NEW.rechazado_por, NEW.usuario_id),
            OLD.estado,
            NEW.estado,
            CASE 
                WHEN NEW.estado = 'rechazado' THEN NEW.motivo_rechazo
                ELSE CONCAT('Cambio de estado: ', OLD.estado, ' → ', NEW.estado)
            END
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requisicion_detalles`
--

CREATE TABLE `requisicion_detalles` (
  `id` int NOT NULL,
  `requisicion_id` int NOT NULL,
  `poa_item_id` int NOT NULL,
  `cantidad_solicitada` int NOT NULL,
  `precio_unitario_aplicado` decimal(10,2) NOT NULL COMMENT 'Precio al momento de la requisición',
  `subtotal` decimal(12,2) GENERATED ALWAYS AS ((`cantidad_solicitada` * `precio_unitario_aplicado`)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Disparadores `requisicion_detalles`
--
DELIMITER $$
CREATE TRIGGER `trg_actualizar_inventario_poa` AFTER INSERT ON `requisicion_detalles` FOR EACH ROW BEGIN
    UPDATE poa_items
    SET cantidad_disponible = cantidad_disponible - NEW.cantidad_solicitada,
        monto_comprometido = monto_comprometido + (NEW.cantidad_solicitada * NEW.precio_unitario_aplicado)
    WHERE id = NEW.poa_item_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_restaurar_inventario_poa` AFTER DELETE ON `requisicion_detalles` FOR EACH ROW BEGIN
    UPDATE poa_items
    SET cantidad_disponible = cantidad_disponible + OLD.cantidad_solicitada,
        monto_comprometido = monto_comprometido - (OLD.cantidad_solicitada * OLD.precio_unitario_aplicado)
    WHERE id = OLD.poa_item_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requisicion_historial`
--

CREATE TABLE `requisicion_historial` (
  `id` int NOT NULL,
  `requisicion_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `estado_anterior` enum('pendiente','validado_planeacion','aprobado_direccion','rechazado','surtido') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_nuevo` enum('pendiente','validado_planeacion','aprobado_direccion','rechazado','surtido') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comentario` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_cambio` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auditoría de cambios en requisiciones';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nombre_usuario` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('director_planeacion','area_operativa','usuario') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'usuario',
  `area_id` int DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `intentos_fallidos` int DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_sesiones`
--

CREATE TABLE `usuario_sesiones` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `token_sesion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fecha_inicio` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` datetime NOT NULL,
  `activa` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_poa_disponibilidad`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_poa_disponibilidad` (
`anio_fiscal` int
,`area` varchar(100)
,`cantidad_disponible` int
,`cantidad_original` int
,`cantidad_utilizada` bigint
,`concepto` varchar(500)
,`monto_comprometido` decimal(12,2)
,`monto_ejercido` decimal(12,2)
,`partida_presupuestal` varchar(20)
,`porcentaje_utilizado` decimal(17,2)
,`precio_unitario` decimal(10,2)
,`presupuesto_disponible_item` decimal(20,2)
,`presupuesto_total_item` decimal(20,2)
,`proceso` varchar(255)
,`programa` varchar(255)
,`responsable_area` varchar(150)
,`unidad_medida` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_presupuesto_areas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_presupuesto_areas` (
`anio_fiscal` int
,`area` varchar(100)
,`porcentaje_comprometido` decimal(21,2)
,`porcentaje_disponible` decimal(21,2)
,`porcentaje_ejercido` decimal(21,2)
,`presupuesto_asignado` decimal(15,2)
,`presupuesto_comprometido` decimal(15,2)
,`presupuesto_disponible` decimal(15,2)
,`presupuesto_ejercido` decimal(15,2)
,`responsable_area` varchar(150)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_requisiciones_detalle`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_requisiciones_detalle` (
`area` varchar(100)
,`cantidad_solicitada` int
,`concepto` varchar(500)
,`estado` enum('pendiente','validado_planeacion','aprobado_direccion','rechazado','surtido')
,`fecha_solicitud` date
,`folio` varchar(20)
,`motivo_solicitud` text
,`observaciones_usuario` text
,`partida_presupuestal` varchar(20)
,`precio_unitario_aplicado` decimal(10,2)
,`solicitante` varchar(50)
,`subtotal` decimal(12,2)
,`unidad_medida` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_requisiciones_resumen`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_requisiciones_resumen` (
`aprobado_por_nombre` varchar(50)
,`area` varchar(100)
,`estado` enum('pendiente','validado_planeacion','aprobado_direccion','rechazado','surtido')
,`fecha_aprobacion_direccion` datetime
,`fecha_creacion` datetime
,`fecha_solicitud` date
,`fecha_validacion_planeacion` datetime
,`folio` varchar(20)
,`id` int
,`monto_total` decimal(34,2)
,`motivo_rechazo` text
,`motivo_solicitud` text
,`rechazado_por_nombre` varchar(50)
,`responsable_area` varchar(150)
,`solicitante` varchar(50)
,`total_items` bigint
,`validado_por_nombre` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_poa_disponibilidad`
--
DROP TABLE IF EXISTS `v_poa_disponibilidad`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_poa_disponibilidad`  AS SELECT `pa`.`anio_fiscal` AS `anio_fiscal`, `a`.`nombre_programa` AS `area`, `a`.`responsable_area` AS `responsable_area`, `pi`.`programa` AS `programa`, `pi`.`proceso` AS `proceso`, `pi`.`partida_presupuestal` AS `partida_presupuestal`, `pi`.`concepto` AS `concepto`, `pi`.`unidad_medida` AS `unidad_medida`, `pi`.`precio_unitario` AS `precio_unitario`, `pi`.`cantidad_original` AS `cantidad_original`, `pi`.`cantidad_disponible` AS `cantidad_disponible`, (`pi`.`cantidad_original` - `pi`.`cantidad_disponible`) AS `cantidad_utilizada`, `pi`.`monto_comprometido` AS `monto_comprometido`, `pi`.`monto_ejercido` AS `monto_ejercido`, (`pi`.`cantidad_original` * `pi`.`precio_unitario`) AS `presupuesto_total_item`, (`pi`.`cantidad_disponible` * `pi`.`precio_unitario`) AS `presupuesto_disponible_item`, round((((`pi`.`cantidad_original` - `pi`.`cantidad_disponible`) / `pi`.`cantidad_original`) * 100),2) AS `porcentaje_utilizado` FROM ((`poa_items` `pi` join `areas` `a` on((`pi`.`area_id` = `a`.`id`))) join `poa_archivos` `pa` on((`pi`.`poa_archivo_id` = `pa`.`id`))) WHERE (`pa`.`estado` = 'activo') ORDER BY `a`.`nombre_programa` ASC, `pi`.`partida_presupuestal` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_presupuesto_areas`
--
DROP TABLE IF EXISTS `v_presupuesto_areas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_presupuesto_areas`  AS SELECT `a`.`nombre_programa` AS `area`, `a`.`responsable_area` AS `responsable_area`, `paa`.`anio_fiscal` AS `anio_fiscal`, `paa`.`presupuesto_asignado` AS `presupuesto_asignado`, `paa`.`presupuesto_comprometido` AS `presupuesto_comprometido`, `paa`.`presupuesto_ejercido` AS `presupuesto_ejercido`, `paa`.`presupuesto_disponible` AS `presupuesto_disponible`, round(((`paa`.`presupuesto_ejercido` / nullif(`paa`.`presupuesto_asignado`,0)) * 100),2) AS `porcentaje_ejercido`, round(((`paa`.`presupuesto_comprometido` / nullif(`paa`.`presupuesto_asignado`,0)) * 100),2) AS `porcentaje_comprometido`, round(((`paa`.`presupuesto_disponible` / nullif(`paa`.`presupuesto_asignado`,0)) * 100),2) AS `porcentaje_disponible` FROM (`presupuesto_area_anual` `paa` join `areas` `a` on((`paa`.`area_id` = `a`.`id`))) ORDER BY `paa`.`anio_fiscal` DESC, `a`.`nombre_programa` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_requisiciones_detalle`
--
DROP TABLE IF EXISTS `v_requisiciones_detalle`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_requisiciones_detalle`  AS SELECT `r`.`folio` AS `folio`, `r`.`fecha_solicitud` AS `fecha_solicitud`, `a`.`nombre_programa` AS `area`, `u`.`nombre_usuario` AS `solicitante`, `r`.`estado` AS `estado`, `pi`.`partida_presupuestal` AS `partida_presupuestal`, `pi`.`concepto` AS `concepto`, `pi`.`unidad_medida` AS `unidad_medida`, `rd`.`cantidad_solicitada` AS `cantidad_solicitada`, `rd`.`precio_unitario_aplicado` AS `precio_unitario_aplicado`, `rd`.`subtotal` AS `subtotal`, `r`.`motivo_solicitud` AS `motivo_solicitud`, `r`.`observaciones_usuario` AS `observaciones_usuario` FROM ((((`requisiciones` `r` join `areas` `a` on((`r`.`area_id` = `a`.`id`))) join `usuarios` `u` on((`r`.`usuario_id` = `u`.`id`))) join `requisicion_detalles` `rd` on((`r`.`id` = `rd`.`requisicion_id`))) join `poa_items` `pi` on((`rd`.`poa_item_id` = `pi`.`id`))) ORDER BY `r`.`fecha_solicitud` DESC, `r`.`id` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_requisiciones_resumen`
--
DROP TABLE IF EXISTS `v_requisiciones_resumen`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_requisiciones_resumen`  AS SELECT `r`.`id` AS `id`, `r`.`folio` AS `folio`, `r`.`fecha_solicitud` AS `fecha_solicitud`, `a`.`nombre_programa` AS `area`, `a`.`responsable_area` AS `responsable_area`, `u`.`nombre_usuario` AS `solicitante`, `r`.`motivo_solicitud` AS `motivo_solicitud`, `r`.`estado` AS `estado`, count(`rd`.`id`) AS `total_items`, sum(`rd`.`subtotal`) AS `monto_total`, `r`.`fecha_creacion` AS `fecha_creacion`, `uv`.`nombre_usuario` AS `validado_por_nombre`, `r`.`fecha_validacion_planeacion` AS `fecha_validacion_planeacion`, `ua`.`nombre_usuario` AS `aprobado_por_nombre`, `r`.`fecha_aprobacion_direccion` AS `fecha_aprobacion_direccion`, `ur`.`nombre_usuario` AS `rechazado_por_nombre`, `r`.`motivo_rechazo` AS `motivo_rechazo` FROM ((((((`requisiciones` `r` join `areas` `a` on((`r`.`area_id` = `a`.`id`))) join `usuarios` `u` on((`r`.`usuario_id` = `u`.`id`))) left join `requisicion_detalles` `rd` on((`r`.`id` = `rd`.`requisicion_id`))) left join `usuarios` `uv` on((`r`.`validado_por` = `uv`.`id`))) left join `usuarios` `ua` on((`r`.`aprobado_por` = `ua`.`id`))) left join `usuarios` `ur` on((`r`.`rechazado_por` = `ur`.`id`))) GROUP BY `r`.`id` ORDER BY `r`.`fecha_solicitud` DESC, `r`.`id` DESC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_programa` (`nombre_programa`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `poa_archivos`
--
ALTER TABLE `poa_archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_poa_cargado_por` (`cargado_por`);

--
-- Indices de la tabla `poa_items`
--
ALTER TABLE `poa_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `poa_archivo_id` (`poa_archivo_id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `fk_poa_item_created_by` (`created_by`);

--
-- Indices de la tabla `presupuesto_area_anual`
--
ALTER TABLE `presupuesto_area_anual`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_area_anio` (`area_id`,`anio_fiscal`),
  ADD KEY `idx_anio_fiscal` (`anio_fiscal`);

--
-- Indices de la tabla `requisiciones`
--
ALTER TABLE `requisiciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `folio` (`folio`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `fk_validado_por` (`validado_por`),
  ADD KEY `fk_aprobado_por` (`aprobado_por`),
  ADD KEY `fk_rechazado_por` (`rechazado_por`);

--
-- Indices de la tabla `requisicion_detalles`
--
ALTER TABLE `requisicion_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requisicion_id` (`requisicion_id`),
  ADD KEY `poa_item_id` (`poa_item_id`);

--
-- Indices de la tabla `requisicion_historial`
--
ALTER TABLE `requisicion_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requisicion_id` (`requisicion_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD KEY `area_id` (`area_id`);

--
-- Indices de la tabla `usuario_sesiones`
--
ALTER TABLE `usuario_sesiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `poa_archivos`
--
ALTER TABLE `poa_archivos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `poa_items`
--
ALTER TABLE `poa_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `presupuesto_area_anual`
--
ALTER TABLE `presupuesto_area_anual`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `requisiciones`
--
ALTER TABLE `requisiciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `requisicion_detalles`
--
ALTER TABLE `requisicion_detalles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `requisicion_historial`
--
ALTER TABLE `requisicion_historial`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario_sesiones`
--
ALTER TABLE `usuario_sesiones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `poa_archivos`
--
ALTER TABLE `poa_archivos`
  ADD CONSTRAINT `fk_poa_cargado_por` FOREIGN KEY (`cargado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `poa_items`
--
ALTER TABLE `poa_items`
  ADD CONSTRAINT `fk_poa_item_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `poa_items_ibfk_1` FOREIGN KEY (`poa_archivo_id`) REFERENCES `poa_archivos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `poa_items_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`);

--
-- Filtros para la tabla `presupuesto_area_anual`
--
ALTER TABLE `presupuesto_area_anual`
  ADD CONSTRAINT `presupuesto_area_anual_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`);

--
-- Filtros para la tabla `requisiciones`
--
ALTER TABLE `requisiciones`
  ADD CONSTRAINT `fk_aprobado_por` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_rechazado_por` FOREIGN KEY (`rechazado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_validado_por` FOREIGN KEY (`validado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `requisiciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `requisiciones_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`);

--
-- Filtros para la tabla `requisicion_detalles`
--
ALTER TABLE `requisicion_detalles`
  ADD CONSTRAINT `requisicion_detalles_ibfk_1` FOREIGN KEY (`requisicion_id`) REFERENCES `requisiciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requisicion_detalles_ibfk_2` FOREIGN KEY (`poa_item_id`) REFERENCES `poa_items` (`id`);

--
-- Filtros para la tabla `requisicion_historial`
--
ALTER TABLE `requisicion_historial`
  ADD CONSTRAINT `requisicion_historial_ibfk_1` FOREIGN KEY (`requisicion_id`) REFERENCES `requisiciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requisicion_historial_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`);

--
-- Filtros para la tabla `usuario_sesiones`
--
ALTER TABLE `usuario_sesiones`
  ADD CONSTRAINT `usuario_sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
