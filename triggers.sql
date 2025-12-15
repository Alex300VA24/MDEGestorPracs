USE [GESTIONPRACTICANTES]
GO

-- =============================================
-- TRIGGERS PARA SISTEMA DE GESTIÓN DE PRACTICANTES
-- =============================================

-- =============================================
-- TRIGGER INSERT PRACTICANTE
-- =============================================
IF OBJECT_ID('dbo.trg_Practicante_Insert', 'TR') IS NOT NULL 
    DROP TRIGGER dbo.trg_Practicante_Insert;
GO

CREATE TRIGGER [dbo].[trg_Practicante_Insert]
ON [dbo].[Practicante]
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    
    INSERT INTO ActividadReciente (
        TipoActividad, 
        TablaAfectada, 
        Descripcion, 
        UsuarioAfectado, 
        RegistroID, 
        Icono
    )
    SELECT 
        'INSERT',
        'Practicante',
        'Nuevo practicante registrado: ' + i.Nombres + ' ' + i.ApellidoPaterno + 
        ' - ' + ISNULL(i.Carrera, 'Sin carrera'),
        i.Nombres + ' ' + i.ApellidoPaterno + ' ' + ISNULL(i.ApellidoMaterno, ''),
        i.PracticanteID,
        'user-check'
    FROM inserted i;
END;
GO

-- =============================================
-- TRIGGER UPDATE PRACTICANTE
-- =============================================
IF OBJECT_ID('dbo.trg_Practicante_Update', 'TR') IS NOT NULL 
    DROP TRIGGER dbo.trg_Practicante_Update;
GO

CREATE TRIGGER [dbo].[trg_Practicante_Update]
ON [dbo].[Practicante]
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @Descripcion VARCHAR(255);
    DECLARE @Icono VARCHAR(50);
    
    SELECT 
        @Descripcion = 
            CASE 
                WHEN i.EstadoID != d.EstadoID THEN 
                    'Estado cambiado para practicante: ' + i.Nombres + ' ' + i.ApellidoPaterno + 
                    ' (Estado: ' + CAST(i.EstadoID AS VARCHAR) + ')'
                WHEN ISNULL(i.FechaEntrada, '1900-01-01') != ISNULL(d.FechaEntrada, '1900-01-01') 
                  OR ISNULL(i.FechaSalida, '1900-01-01') != ISNULL(d.FechaSalida, '1900-01-01') THEN 
                    'Fechas de práctica actualizadas: ' + i.Nombres + ' ' + i.ApellidoPaterno
                WHEN ISNULL(i.Email, '') != ISNULL(d.Email, '') OR ISNULL(i.Telefono, '') != ISNULL(d.Telefono, '') THEN 
                    'Datos de contacto actualizados: ' + i.Nombres + ' ' + i.ApellidoPaterno
                WHEN ISNULL(i.Carrera, '') != ISNULL(d.Carrera, '') OR ISNULL(i.Universidad, '') != ISNULL(d.Universidad, '') THEN 
                    'Información académica actualizada: ' + i.Nombres + ' ' + i.ApellidoPaterno
                ELSE 
                    'Datos personales actualizados: ' + i.Nombres + ' ' + i.ApellidoPaterno
            END,
        @Icono = 
            CASE 
                WHEN i.EstadoID != d.EstadoID THEN 'user-check'
                WHEN ISNULL(i.FechaEntrada, '1900-01-01') != ISNULL(d.FechaEntrada, '1900-01-01') 
                  OR ISNULL(i.FechaSalida, '1900-01-01') != ISNULL(d.FechaSalida, '1900-01-01') THEN 'calendar'
                WHEN ISNULL(i.Email, '') != ISNULL(d.Email, '') OR ISNULL(i.Telefono, '') != ISNULL(d.Telefono, '') THEN 'phone'
                WHEN ISNULL(i.Carrera, '') != ISNULL(d.Carrera, '') OR ISNULL(i.Universidad, '') != ISNULL(d.Universidad, '') THEN 'graduation-cap'
                ELSE 'edit'
            END
    FROM inserted i
    INNER JOIN deleted d ON i.PracticanteID = d.PracticanteID;
    
    INSERT INTO ActividadReciente (
        TipoActividad, 
        TablaAfectada, 
        Descripcion, 
        UsuarioAfectado, 
        RegistroID, 
        Icono
    )
    SELECT 
        'UPDATE',
        'Practicante',
        @Descripcion,
        i.Nombres + ' ' + i.ApellidoPaterno + ' ' + ISNULL(i.ApellidoMaterno, ''),
        i.PracticanteID,
        @Icono
    FROM inserted i
    INNER JOIN deleted d ON i.PracticanteID = d.PracticanteID;
END;
GO

-- =============================================
-- TRIGGER DELETE PRACTICANTE
-- =============================================
IF OBJECT_ID('dbo.trg_Practicante_Delete', 'TR') IS NOT NULL 
    DROP TRIGGER dbo.trg_Practicante_Delete;
GO

CREATE TRIGGER [dbo].[trg_Practicante_Delete]
ON [dbo].[Practicante]
AFTER DELETE
AS
BEGIN
    SET NOCOUNT ON;
    
    INSERT INTO ActividadReciente (
        TipoActividad, 
        TablaAfectada, 
        Descripcion, 
        UsuarioAfectado, 
        RegistroID, 
        Icono
    )
    SELECT 
        'DELETE',
        'Practicante',
        'Practicante eliminado: ' + d.Nombres + ' ' + d.ApellidoPaterno,
        d.Nombres + ' ' + d.ApellidoPaterno + ' ' + ISNULL(d.ApellidoMaterno, ''),
        d.PracticanteID,
        'trash'
    FROM deleted d;
END;
GO

-- =============================================
-- TRIGGER INSERT USUARIO
-- =============================================
IF OBJECT_ID('dbo.trg_Usuario_Insert', 'TR') IS NOT NULL 
    DROP TRIGGER dbo.trg_Usuario_Insert;
GO

CREATE TRIGGER [dbo].[trg_Usuario_Insert]
ON [dbo].[Usuario]
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    
    INSERT INTO ActividadReciente (
        TipoActividad, 
        TablaAfectada, 
        Descripcion, 
        UsuarioAfectado, 
        RegistroID, 
        Icono
    )
    SELECT 
        'INSERT',
        'Usuario',
        'Nuevo usuario registrado: ' + i.Nombres + ' ' + i.ApellidoPaterno,
        i.Nombres + ' ' + i.ApellidoPaterno + ' ' + ISNULL(i.ApellidoMaterno, ''),
        i.UsuarioID,
        'user-plus'
    FROM inserted i;
END;
GO

-- =============================================
-- TRIGGER UPDATE USUARIO
-- =============================================
IF OBJECT_ID('dbo.trg_Usuario_Update', 'TR') IS NOT NULL 
    DROP TRIGGER dbo.trg_Usuario_Update;
GO

CREATE TRIGGER [dbo].[trg_Usuario_Update]
ON [dbo].[Usuario]
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @Descripcion VARCHAR(255);
    DECLARE @Icono VARCHAR(50);
    
    SELECT 
        @Descripcion = 
            CASE 
                WHEN i.EstadoID != d.EstadoID THEN 
                    'Estado de usuario cambiado: ' + i.Nombres + ' ' + i.ApellidoPaterno
                WHEN ISNULL(i.CargoID, 0) != ISNULL(d.CargoID, 0) THEN 
                    'Cargo actualizado para: ' + i.Nombres + ' ' + i.ApellidoPaterno
                WHEN ISNULL(i.AreaID, 0) != ISNULL(d.AreaID, 0) THEN 
                    'Área cambiada para: ' + i.Nombres + ' ' + i.ApellidoPaterno
                WHEN ISNULL(i.Password, '') != ISNULL(d.Password, '') THEN 
                    'Contraseña actualizada: ' + i.Nombres + ' ' + i.ApellidoPaterno
                ELSE 
                    'Datos personales actualizados: ' + i.Nombres + ' ' + i.ApellidoPaterno
            END,
        @Icono = 
            CASE 
                WHEN i.EstadoID != d.EstadoID THEN 'user-check'
                WHEN ISNULL(i.CargoID, 0) != ISNULL(d.CargoID, 0) THEN 'user-tag'
                WHEN ISNULL(i.AreaID, 0) != ISNULL(d.AreaID, 0) THEN 'users'
                WHEN ISNULL(i.Password, '') != ISNULL(d.Password, '') THEN 'key'
                ELSE 'user-edit'
            END
    FROM inserted i
    INNER JOIN deleted d ON i.UsuarioID = d.UsuarioID;
    
    INSERT INTO ActividadReciente (
        TipoActividad, 
        TablaAfectada, 
        Descripcion, 
        UsuarioAfectado, 
        RegistroID, 
        Icono
    )
    SELECT 
        'UPDATE',
        'Usuario',
        @Descripcion,
        i.Nombres + ' ' + i.ApellidoPaterno + ' ' + ISNULL(i.ApellidoMaterno, ''),
        i.UsuarioID,
        @Icono
    FROM inserted i
    INNER JOIN deleted d ON i.UsuarioID = d.UsuarioID;
END;
GO

-- =============================================
-- TRIGGER DELETE USUARIO
-- =============================================
IF OBJECT_ID('dbo.trg_Usuario_Delete', 'TR') IS NOT NULL 
    DROP TRIGGER dbo.trg_Usuario_Delete;
GO

CREATE TRIGGER [dbo].[trg_Usuario_Delete]
ON [dbo].[Usuario]
AFTER DELETE
AS
BEGIN
    SET NOCOUNT ON;
    
    INSERT INTO ActividadReciente (
        TipoActividad, 
        TablaAfectada, 
        Descripcion, 
        UsuarioAfectado, 
        RegistroID, 
        Icono
    )
    SELECT 
        'DELETE',
        'Usuario',
        'Usuario eliminado: ' + d.Nombres + ' ' + d.ApellidoPaterno,
        d.Nombres + ' ' + d.ApellidoPaterno + ' ' + ISNULL(d.ApellidoMaterno, ''),
        d.UsuarioID,
        'user-x'
    FROM deleted d;
END;
GO

-- =============================================
-- TRIGGER INSERT ASISTENCIA
-- =============================================
IF OBJECT_ID('dbo.trg_Asistencia_Insert', 'TR') IS NOT NULL 
    DROP TRIGGER dbo.trg_Asistencia_Insert;
GO

CREATE TRIGGER [dbo].[trg_Asistencia_Insert]
ON [dbo].[Asistencia]
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    
    INSERT INTO ActividadReciente (
        TipoActividad, 
        TablaAfectada, 
        Descripcion, 
        UsuarioAfectado, 
        RegistroID, 
        Icono
    )
    SELECT 
        'INSERT',
        'Asistencia',
        'Asistencia registrada para ' + p.Nombres + ' ' + p.ApellidoPaterno + 
        ' - ' + CONVERT(VARCHAR, i.Fecha, 103) + 
        ' (' + CONVERT(VARCHAR, i.HoraEntrada, 108) + ')',
        p.Nombres + ' ' + p.ApellidoPaterno,
        i.AsistenciaID,
        'calendar-check'
    FROM inserted i
    INNER JOIN Practicante p ON i.PracticanteID = p.PracticanteID;
END;
GO

-- =============================================
-- TRIGGER UPDATE ASISTENCIA
-- =============================================
IF OBJECT_ID('dbo.trg_Asistencia_Update', 'TR') IS NOT NULL 
    DROP TRIGGER dbo.trg_Asistencia_Update;
GO

CREATE TRIGGER [dbo].[trg_Asistencia_Update]
ON [dbo].[Asistencia]
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @Descripcion VARCHAR(255);
    DECLARE @Icono VARCHAR(50);
    
    SELECT 
        @Descripcion = 
            CASE
                WHEN ISNULL(i.HoraEntrada, '00:00:00') != ISNULL(d.HoraEntrada, '00:00:00')
                 AND ISNULL(i.HoraSalida, '00:00:00') != ISNULL(d.HoraSalida, '00:00:00') THEN
                    'Horarios de entrada y salida modificados: ' + p.Nombres + ' ' + p.ApellidoPaterno +
                    ' (' + CONVERT(VARCHAR, i.Fecha, 103) + ')'
                WHEN ISNULL(i.HoraEntrada, '00:00:00') != ISNULL(d.HoraEntrada, '00:00:00') THEN
                    'Hora de entrada modificada: ' + p.Nombres + ' ' + p.ApellidoPaterno +
                    ' (' + CONVERT(VARCHAR, i.Fecha, 103) + ' ' + CONVERT(VARCHAR, i.HoraEntrada, 108) + ')'
                WHEN ISNULL(i.HoraSalida, '00:00:00') != ISNULL(d.HoraSalida, '00:00:00') THEN
                    'Hora de salida modificada: ' + p.Nombres + ' ' + p.ApellidoPaterno +
                    ' (' + CONVERT(VARCHAR, i.Fecha, 103) + ' ' + CONVERT(VARCHAR, i.HoraSalida, 108) + ')'
                WHEN ISNULL(i.TurnoID, 0) != ISNULL(d.TurnoID, 0) THEN
                    'Turno modificado: ' + p.Nombres + ' ' + p.ApellidoPaterno +
                    ' (' + CONVERT(VARCHAR, i.Fecha, 103) + ')'
                ELSE
                    'Asistencia actualizada: ' + p.Nombres + ' ' + p.ApellidoPaterno
            END,
        @Icono = 
            CASE 
                WHEN ISNULL(i.HoraEntrada, '00:00:00') != ISNULL(d.HoraEntrada, '00:00:00') THEN 'clock'
                WHEN ISNULL(i.HoraSalida, '00:00:00') != ISNULL(d.HoraSalida, '00:00:00') THEN 'clock'
                WHEN ISNULL(i.TurnoID, 0) != ISNULL(d.TurnoID, 0) THEN 'calendar-alt'
                ELSE 'calendar-edit'
            END
    FROM inserted i
    INNER JOIN deleted d ON i.AsistenciaID = d.AsistenciaID
    INNER JOIN Practicante p ON i.PracticanteID = p.PracticanteID;
    
    INSERT INTO ActividadReciente (
        TipoActividad, 
        TablaAfectada, 
        Descripcion, 
        UsuarioAfectado, 
        RegistroID, 
        Icono
    )
    SELECT 
        'UPDATE',
        'Asistencia',
        @Descripcion,
        p.Nombres + ' ' + p.ApellidoPaterno,
        i.AsistenciaID,
        @Icono
    FROM inserted i
    INNER JOIN deleted d ON i.AsistenciaID = d.AsistenciaID
    INNER JOIN Practicante p ON i.PracticanteID = p.PracticanteID;
END;
GO

-- =============================================
-- TRIGGER UPDATE SOLICITUDPRACTICAS
-- =============================================
IF OBJECT_ID('dbo.trg_SolicitudPracticas_Update', 'TR') IS NOT NULL 
    DROP TRIGGER dbo.trg_SolicitudPracticas_Update;
GO

CREATE TRIGGER [dbo].[trg_SolicitudPracticas_Update]
ON [dbo].[SolicitudPracticas]
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @Descripcion VARCHAR(255);
    DECLARE @Icono VARCHAR(50);
    
    SELECT 
        @Descripcion = 
            CASE 
                WHEN i.EstadoID != d.EstadoID THEN 
                    CASE i.EstadoID
                        WHEN 7 THEN 'Solicitud APROBADA: ' + p.Nombres + ' ' + p.ApellidoPaterno
                        WHEN 8 THEN 'Solicitud RECHAZADA: ' + p.Nombres + ' ' + p.ApellidoPaterno
                        WHEN 6 THEN 'Solicitud en REVISIÓN: ' + p.Nombres + ' ' + p.ApellidoPaterno
                        ELSE 'Estado de solicitud cambiado: ' + p.Nombres + ' ' + p.ApellidoPaterno
                    END
                WHEN ISNULL(i.AreaID, 0) != ISNULL(d.AreaID, 0) THEN 
                    'Área de práctica cambiada: ' + p.Nombres + ' ' + p.ApellidoPaterno
                WHEN ISNULL(i.FechaSolicitud, '1900-01-01') != ISNULL(d.FechaSolicitud, '1900-01-01') THEN 
                    'Fecha de solicitud actualizada: ' + p.Nombres + ' ' + p.ApellidoPaterno
                ELSE 
                    'Solicitud actualizada: ' + p.Nombres + ' ' + p.ApellidoPaterno
            END,
        @Icono = 
            CASE 
                WHEN i.EstadoID != d.EstadoID THEN 
                    CASE i.EstadoID
                        WHEN 7 THEN 'check-circle'
                        WHEN 8 THEN 'times-circle'
                        WHEN 6 THEN 'hourglass-half'
                        ELSE 'file-edit'
                    END
                WHEN ISNULL(i.AreaID, 0) != ISNULL(d.AreaID, 0) THEN 'exchange-alt'
                ELSE 'file-edit'
            END
    FROM inserted i
    INNER JOIN deleted d ON i.SolicitudID = d.SolicitudID
    INNER JOIN Practicante p ON i.PracticanteID = p.PracticanteID;
    
    INSERT INTO ActividadReciente (
        TipoActividad, 
        TablaAfectada, 
        Descripcion, 
        UsuarioAfectado, 
        RegistroID, 
        Icono
    )
    SELECT 
        'UPDATE',
        'SolicitudPracticas',
        @Descripcion,
        p.Nombres + ' ' + p.ApellidoPaterno,
        i.SolicitudID,
        @Icono
    FROM inserted i
    INNER JOIN deleted d ON i.SolicitudID = d.SolicitudID
    INNER JOIN Practicante p ON i.PracticanteID = p.PracticanteID;
END;
GO

-- =============================================
-- FIN DE TRIGGERS
-- =============================================
PRINT '================================================';
PRINT 'TRIGGERS INSTALADOS CORRECTAMENTE';
PRINT '================================================';
PRINT 'Triggers creados:';
PRINT '  - trg_Practicante_Insert';
PRINT '  - trg_Practicante_Update';
PRINT '  - trg_Practicante_Delete';
PRINT '  - trg_Usuario_Insert';
PRINT '  - trg_Usuario_Update';
PRINT '  - trg_Usuario_Delete';
PRINT '  - trg_Asistencia_Insert';
PRINT '  - trg_Asistencia_Update';
PRINT '  - trg_SolicitudPracticas_Update';
PRINT '================================================';
GO