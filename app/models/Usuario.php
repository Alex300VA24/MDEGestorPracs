<?php

namespace App\Models;

use App\Models\Cargo;
use App\Models\Area;

class Usuario
{
    /* ===== ATRIBUTOS ===== */

    private ?int $usuarioID = null;
    private ?string $nombreUsuario = null;
    private ?string $nombres = null;
    private ?string $apellidoPaterno = null;
    private ?string $apellidoMaterno = null;
    private ?string $password = null;
    private ?string $dni = null;
    private ?string $cui = null;
    private ?string $estado = null;
    private ?int $estadoID = null;

    // Relaciones (IDs)
    private ?int $cargoID = null;
    private ?int $areaID = null;

    // Objetos relacionados
    private ?Cargo $cargo = null;
    private ?Area $area = null;

    /* ===== CONSTRUCTOR ===== */

    public function __construct(array $data = [])
    {
        $this->usuarioID = $data['usuarioID'] ?? null;
        $this->nombreUsuario = $data['nombreUsuario'] ?? null;
        $this->nombres = $data['nombres'] ?? null;
        $this->apellidoPaterno = $data['apellidoPaterno'] ?? null;
        $this->apellidoMaterno = $data['apellidoMaterno'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->dni = $data['dni'] ?? null;
        $this->cui = $data['cui'] ?? null;
        $this->estado = $data['estado'] ?? null;
        $this->estadoID = $data['estadoID'] ?? null;

        // Relaciones por ID (BD)
        $this->cargoID = $data['cargoID'] ?? null;
        $this->areaID  = $data['areaID'] ?? null;
    }

    /* ===== GETTERS ===== */

    public function getUsuarioID(): ?int { return $this->usuarioID; }
    public function getNombreUsuario(): ?string { return $this->nombreUsuario; }
    public function getNombres(): ?string { return $this->nombres; }
    public function getApellidoPaterno(): ?string { return $this->apellidoPaterno; }
    public function getApellidoMaterno(): ?string { return $this->apellidoMaterno; }
    public function getPassword(): ?string { return $this->password; }
    public function getDNI(): ?string { return $this->dni; }
    public function getCUI(): ?string { return $this->cui; }
    public function getEstado(): ?string { return $this->estado; }
    public function getEstadoID(): ?int { return $this->estadoID; }

    public function getCargoID(): ?int { return $this->cargoID; }
    public function getAreaID(): ?int { return $this->areaID; }

    public function getCargo(): ?Cargo { return $this->cargo; }
    public function getArea(): ?Area { return $this->area; }

    /* ===== SETTERS ===== */

    public function setUsuarioID(int $id): void { $this->usuarioID = $id; }
    public function setNombreUsuario(string $v): void { $this->nombreUsuario = $v; }
    public function setNombres(string $v): void { $this->nombres = $v; }
    public function setApellidoPaterno(string $v): void { $this->apellidoPaterno = $v; }
    public function setApellidoMaterno(string $v): void { $this->apellidoMaterno = $v; }
    public function setPassword(string $v): void { $this->password = $v; }
    public function setDNI(string $v): void { $this->dni = $v; }
    public function setCUI(string $v): void { $this->cui = $v; }
    public function setEstado(string $v): void { $this->estado = $v; }
    public function setEstadoID(int $v): void { $this->estadoID = $v; }

    public function setCargoID(int $id): void { $this->cargoID = $id; }
    public function setAreaID(int $id): void { $this->areaID = $id; }

    public function setCargo(Cargo $cargo): void { $this->cargo = $cargo; }
    public function setArea(Area $area): void { $this->area = $area; }

    /* ===== MÉTODOS ===== */

    public function getNombreCompleto(): string
    {
        return trim("{$this->nombres} {$this->apellidoPaterno} {$this->apellidoMaterno}");
    }

    public function toArray(): array
    {
        return [
            'usuarioID' => $this->usuarioID,
            'nombreUsuario' => $this->nombreUsuario,
            'nombres' => $this->nombres,
            'apellidoPaterno' => $this->apellidoPaterno,
            'apellidoMaterno' => $this->apellidoMaterno,
            'nombreCompleto' => $this->getNombreCompleto(),
            'dni' => $this->dni,
            'cui' => $this->cui,
            'estado' => $this->estado,
            'cargo' => $this->cargo !== null ? $this->cargo->toArray() : null,
            'area'  => $this->area !== null ? $this->area->toArray() : null,    
        ];
    }
}
