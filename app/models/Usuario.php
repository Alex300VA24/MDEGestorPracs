<?php
namespace App\Models;

class Usuario {
    private $usuarioID;
    private $nombreUsuario;
    private $nombres;
    private $apellidoPaterno;
    private $apellidoMaterno;
    private $password;
    private $dni;
    private $cui;
    private $estado;
    private $estadoID;
    private $cargo;
    private $area;


    
    // Getters
    public function getUsuarioID() { return $this->usuarioID; }
    public function getNombreUsuario() { return $this->nombreUsuario; }
    public function getNombres() { return $this->nombres; }
    public function getApellidoPaterno() { return $this->apellidoPaterno; }
    public function getApellidoMaterno() { return $this->apellidoMaterno; }
    public function getPassword() { return $this->password; }
    public function getDNI() { return $this->dni; }
    public function getCUI() { return $this->cui; }
    public function getEstado() { return $this->estado; }
    public function getCargo() { return $this->cargo; }
    public function getArea() { return $this->area; }
    public function getEstadoID() { return $this->estadoID; }
    
    // Setters
    public function setUsuarioID($usuarioID) { $this->usuarioID = $usuarioID; }
    public function setNombreUsuario($nombreUsuario) { $this->nombreUsuario = $nombreUsuario; }
    public function setNombres($nombres) { $this->nombres = $nombres; }
    public function setApellidoPaterno($apellidoPaterno) { $this->apellidoPaterno = $apellidoPaterno; }
    public function setApellidoMaterno($apellidoMaterno) { $this->apellidoMaterno = $apellidoMaterno; }
    public function setPassword($password) { $this->password = $password; }
    public function setDNI($dni) { $this->dni = $dni; }
    public function setCUI($cui) { $this->cui = $cui; }
    public function setEstado($estado) { $this->estado = $estado; }
    public function setCargo($cargo) { $this->cargo = $cargo; }
    public function setArea($area) { $this->area = $area; }
    public function setEstadoID($estadoID) { $this->estadoID = $estadoID; }
    
    
    public function getNombreCompleto() {
        return "{$this->nombres} {$this->apellidoPaterno} {$this->apellidoMaterno}";
    }
    
    public function toArray() {
        return [
            'usuarioID' => $this->usuarioID,
            'nombreUsuario' => $this->nombreUsuario,
            'nombres' => $this->nombres,
            'apellidoPaterno' => $this->apellidoPaterno,
            'apellidoMaterno' => $this->apellidoMaterno,
            'dni' => $this->dni,
            'cui' => $this->cui,
            'estado' => $this->estado,
            'cargo' => $this->cargo ? $this->cargo->toArray() : null,
            'area' => $this->area ? $this->area->toArray() : null,
            'estadoID' => $this->estadoID,
        ];
    }
}