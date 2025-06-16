-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: db
-- Tiempo de generación: 14-06-2025 a las 09:50:01
-- Versión del servidor: 8.0.40
-- Versión de PHP: 8.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `concesionario`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Imagenes_Vehiculos`
--

CREATE TABLE `Imagenes_Vehiculos` (
  `id_imagen` int NOT NULL,
  `id_vehiculo` int NOT NULL,
  `ruta_servidor` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

--
-- Volcado de datos para la tabla `Imagenes_Vehiculos`
--

INSERT INTO `Imagenes_Vehiculos` (`id_imagen`, `id_vehiculo`, `ruta_servidor`) VALUES
(1, 1, 'C:/Users/Admin/Desktop/Concesionario/images/corolla_img_1.jpg'),
(2, 1, 'C:/Users/Admin/Desktop/Concesionario/images/corolla_img_2.jpg'),
(6, 7, 'images/vehiculo_681f45f7b35fe.png'),
(7, 8, 'images/vehiculo_682ee775a20ff.png'),
(8, 9, 'images/vehiculo_682eeab02e01b.png'),
(9, 10, 'images/vehiculo_682eeb283983c.png'),
(10, 11, 'images/vehiculo_683dea166847f.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Marcas`
--

CREATE TABLE `Marcas` (
  `id_marca` int NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

--
-- Volcado de datos para la tabla `Marcas`
--

INSERT INTO `Marcas` (`id_marca`, `nombre`) VALUES
(6, 'Citroen'),
(7, 'Ferrari'),
(2, 'Ford'),
(5, 'Peugeot'),
(8, 'Porshe'),
(1, 'Toyota');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Modelos`
--

CREATE TABLE `Modelos` (
  `id_modelo` int NOT NULL,
  `id_marca` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `foto_modelo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

--
-- Volcado de datos para la tabla `Modelos`
--

INSERT INTO `Modelos` (`id_modelo`, `id_marca`, `nombre`, `foto_modelo`) VALUES
(1, 1, 'Corolla', 'corolla.jpg'),
(4, 2, 'Fiestero', 'fiesta.jpg'),
(5, 6, 'Sara', NULL),
(6, 6, 'Nuevo', 'Uploads/modelo_681f447c3993d.png'),
(7, 2, 'Fiesta', 'Uploads/modelo_6822ea392eae6.png'),
(8, 5, '307 Ranchera', NULL),
(9, 7, 'Gt', NULL),
(10, 5, '207', NULL),
(11, 8, 'Panamera', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Usuarios`
--

CREATE TABLE `Usuarios` (
  `id_usuario` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `rol` enum('cliente','administrador') NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text,
  `avatar` varchar(255) DEFAULT NULL,
  `theme` enum('light','dark','neon_pink','neon_blue') DEFAULT 'light'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

--
-- Volcado de datos para la tabla `Usuarios`
--

INSERT INTO `Usuarios` (`id_usuario`, `nombre`, `email`, `contraseña`, `rol`, `telefono`, `direccion`, `avatar`, `theme`) VALUES
(1, 'Carlos Pérez', 'carlos@example.com', 'password123', 'cliente', '123456789', 'Calle Ejemplo 123', NULL, 'neon_pink'),
(2, 'Ana García', 'ana@example.com', 'password456', 'administrador', NULL, NULL, NULL, 'light'),
(3, 'Luis Martínez', 'luis@example.com', 'password789', 'cliente', NULL, NULL, NULL, 'light'),
(4, 'Pedro Gómez', 'pedro@example.com', 'password000', 'administrador', NULL, NULL, NULL, 'light'),
(5, 'Lionel Messi', 'LioMessi@gmail.com', '$2y$10$LIxpI6y2UmP5Mg5iyB1Z9O/rstN56JokkVRAtQU9Ti6Ti0CLgxhkS', 'cliente', NULL, NULL, NULL, 'light'),
(6, 'Persona1', 'p@1', '$2y$10$TxbBeNIJEPp.sKMOZKqhmeBlunMxwEoUj2YBKWXjXe1RCF5ezFZ02', 'cliente', NULL, NULL, NULL, 'light'),
(7, 'Carlos Cuevas', 'carloscuevasortiz10@gmail.com', '$2y$10$dpR1q9l3f78tEZ.YZEO/3eF39Xj0ZCkhD7NCNrt0x0YqLuJcDndeq', 'cliente', NULL, NULL, NULL, 'light'),
(8, 'Admin', 'Admin@admin.com', '$2y$10$4eszomGrWhmzc4W0vI1e5ub8QsT3n5f8WJN.7SGfedZNVQGqwqs.G', 'administrador', '123456789', 'Calle Ejemplo 123', 'images/avatars/avatar_8_1749804802.png', 'light'),
(9, 'Carlos', 'carlos10@gmail.com', '$2y$10$hzI4ps.of6.AlQZ5128mj.HbyEdixVyP86eH6byJecA1waFzrV7Bq', 'cliente', NULL, NULL, NULL, 'light'),
(10, 'Carlos', 'xccueort046@ieshnosmachado.org', '$2y$10$Y6D3aUJu8LgIADC8q1eQ8e.AzDpRpOhv7QOZHt/CDWVOKyj81/9w6', 'cliente', NULL, NULL, NULL, 'light'),
(11, 'Naiara', 'naiara@gmail.com', '$2y$10$hM3HDOTtLcf7XZPUo2pN9OmxKr5Kq4xRb4cJRV/JZGdXJCfnW77ou', 'cliente', NULL, NULL, NULL, 'light');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Vehiculos`
--

CREATE TABLE `Vehiculos` (
  `id_vehiculo` int NOT NULL,
  `marca` int NOT NULL,
  `modelo` int NOT NULL,
  `año` int NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `kilometraje` int NOT NULL,
  `tipo_combustible` enum('Gasolina','Diesel','Eléctrico','Híbrido') NOT NULL,
  `transmision` enum('Manual','Automática') NOT NULL,
  `descripcion` text,
  `imagen` varchar(255) DEFAULT NULL,
  `estado` enum('disponible','reservado') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

--
-- Volcado de datos para la tabla `Vehiculos`
--

INSERT INTO `Vehiculos` (`id_vehiculo`, `marca`, `modelo`, `año`, `precio`, `kilometraje`, `tipo_combustible`, `transmision`, `descripcion`, `imagen`, `estado`) VALUES
(1, 1, 1, 2022, 20000.00, 15000, 'Gasolina', 'Automática', 'Toyota Corolla 2022', 'corolla_img.jpg', 'disponible'),
(5, 6, 5, 2000, 10000.00, 0, 'Gasolina', 'Manual', '0', NULL, 'disponible'),
(6, 6, 5, 2000, 2121.00, 1212, 'Gasolina', 'Manual', '121', NULL, 'disponible'),
(7, 6, 6, 2021, 90000.00, 121212, 'Híbrido', 'Automática', '212121', 'images/vehiculo_681f45f7b35fe.png', 'reservado'),
(8, 6, 5, 2000, 150000.00, 0, 'Híbrido', 'Manual', '0', 'images/vehiculo_682ee775a20ff.png', 'disponible'),
(9, 5, 8, 2005, 20000.00, 1000, 'Gasolina', 'Manual', '0', 'images/vehiculo_682eeab02e01b.png', 'disponible'),
(10, 2, 7, 2025, 50000.00, 0, 'Eléctrico', 'Automática', '0', 'images/vehiculo_682eeb283983c.png', 'reservado'),
(11, 5, 8, 2025, 20000000.00, 100000000, 'Eléctrico', 'Manual', '0', 'images/vehiculo_683dea166847f.png', 'disponible'),
(12, 8, 11, 2025, 80000.00, 0, 'Diesel', 'Manual', '0', NULL, 'disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Ventas`
--

CREATE TABLE `Ventas` (
  `id_venta` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_vehiculo` int NOT NULL,
  `fecha_venta` datetime NOT NULL,
  `precio_final` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

--
-- Volcado de datos para la tabla `Ventas`
--

INSERT INTO `Ventas` (`id_venta`, `id_usuario`, `id_vehiculo`, `fecha_venta`, `precio_final`) VALUES
(1, 1, 1, '2025-05-15 15:00:00', 20000.00),
(3, 7, 6, '2025-05-22 09:50:40', 2121.00),
(4, 7, 9, '2025-05-22 09:55:33', 20000.00),
(5, 7, 8, '2025-06-02 18:31:12', 150000.00),
(6, 10, 11, '2025-06-03 06:57:23', 20000.00),
(7, 11, 5, '2025-06-13 08:55:09', 10000.00);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `Imagenes_Vehiculos`
--
ALTER TABLE `Imagenes_Vehiculos`
  ADD PRIMARY KEY (`id_imagen`),
  ADD KEY `id_vehiculo` (`id_vehiculo`);

--
-- Indices de la tabla `Marcas`
--
ALTER TABLE `Marcas`
  ADD PRIMARY KEY (`id_marca`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `Modelos`
--
ALTER TABLE `Modelos`
  ADD PRIMARY KEY (`id_modelo`),
  ADD KEY `id_marca` (`id_marca`);

--
-- Indices de la tabla `Usuarios`
--
ALTER TABLE `Usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `Vehiculos`
--
ALTER TABLE `Vehiculos`
  ADD PRIMARY KEY (`id_vehiculo`),
  ADD KEY `marca` (`marca`),
  ADD KEY `modelo` (`modelo`);

--
-- Indices de la tabla `Ventas`
--
ALTER TABLE `Ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_vehiculo` (`id_vehiculo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `Imagenes_Vehiculos`
--
ALTER TABLE `Imagenes_Vehiculos`
  MODIFY `id_imagen` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `Marcas`
--
ALTER TABLE `Marcas`
  MODIFY `id_marca` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `Modelos`
--
ALTER TABLE `Modelos`
  MODIFY `id_modelo` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `Usuarios`
--
ALTER TABLE `Usuarios`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `Vehiculos`
--
ALTER TABLE `Vehiculos`
  MODIFY `id_vehiculo` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `Ventas`
--
ALTER TABLE `Ventas`
  MODIFY `id_venta` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `Imagenes_Vehiculos`
--
ALTER TABLE `Imagenes_Vehiculos`
  ADD CONSTRAINT `Imagenes_Vehiculos_ibfk_1` FOREIGN KEY (`id_vehiculo`) REFERENCES `Vehiculos` (`id_vehiculo`);

--
-- Filtros para la tabla `Modelos`
--
ALTER TABLE `Modelos`
  ADD CONSTRAINT `Modelos_ibfk_1` FOREIGN KEY (`id_marca`) REFERENCES `Marcas` (`id_marca`);

--
-- Filtros para la tabla `Vehiculos`
--
ALTER TABLE `Vehiculos`
  ADD CONSTRAINT `Vehiculos_ibfk_1` FOREIGN KEY (`marca`) REFERENCES `Marcas` (`id_marca`),
  ADD CONSTRAINT `Vehiculos_ibfk_2` FOREIGN KEY (`modelo`) REFERENCES `Modelos` (`id_modelo`);

--
-- Filtros para la tabla `Ventas`
--
ALTER TABLE `Ventas`
  ADD CONSTRAINT `Ventas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `Usuarios` (`id_usuario`),
  ADD CONSTRAINT `Ventas_ibfk_2` FOREIGN KEY (`id_vehiculo`) REFERENCES `Vehiculos` (`id_vehiculo`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
