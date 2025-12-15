<?php
namespace App\Models;

class Practicante {
    private $practicanteID;
    private $dni;
    private $nombres;
    private $apellidoPaterno;
    private $apellidoMaterno;
    private $genero;
    private $carrera;
    private $email;
    private $telefono;
    private $direccion;
    private $universidad;
    private $fechaEntrada;
    private $fechaSalida;
    private $fechaRegistro;
    private $estadoID;
    
    // Getters
    public function getPracticanteID() { return $this->practicanteID; }
    public function getDNI() { return $this->dni; }
    public function getNombres() { return $this->nombres; }
    public function getApellidoPaterno() { return $this->apellidoPaterno; }
    public function getApellidoMaterno() { return $this->apellidoMaterno; }
    public function getGenero() { return $this->genero; }
    public function getCarrera() { return $this->carrera; }
    public function getEmail() { return $this->email; }
    public function getTelefono() { return $this->telefono; }
    public function getDireccion() { return $this->direccion; }
    public function getUniversidad() { return $this->universidad; }
    public function getFechaEntrada() { return $this->fechaEntrada; }
    public function getFechaSalida() { return $this->fechaSalida; }
    public function getFechaRegistro() { return $this->fechaRegistro; }
    public function getEstadoID() { return $this->estadoID; }
    
    // Setters
    public function setPracticanteID($practicanteID) { $this->practicanteID = $practicanteID; }
    public function setDNI($dni) { $this->dni = $dni; }
    public function setNombres($nombres) { $this->nombres = $nombres; }
    public function setApellidoPaterno($apellidoPaterno) { $this->apellidoPaterno = $apellidoPaterno; }
    public function setApellidoMaterno($apellidoMaterno) { $this->apellidoMaterno = $apellidoMaterno; }
    public function setGenero($genero) { $this->genero = $genero; }
    public function setCarrera($carrera) { $this->carrera = $carrera; }
    public function setEmail($email) { $this->email = $email; }
    public function setTelefono($telefono) { $this->telefono = $telefono; }
    public function setDireccion($direccion) { $this->direccion = $direccion; }
    public function setUniversidad($universidad) { $this->universidad = $universidad; }
    public function setFechaEntrada($fechaEntrada) { $this->fechaEntrada = $fechaEntrada; }
    public function setFechaSalida($fechaSalida) { $this->fechaSalida = $fechaSalida; }
    public function setFechaRegistro($fechaRegistro) { $this->fechaRegistro = $fechaRegistro; }
    public function setEstadoID($estadoID) { $this->estadoID = $estadoID; }
    
    public function getNombreCompleto() {
        return "{$this->nombres} {$this->apellidoPaterno} {$this->apellidoMaterno}";
    }
    
    public function toArray() {
        return [
            'practicanteID' => $this->practicanteID,
            'dni' => $this->dni,
            'nombres' => $this->nombres,
            'apellidoPaterno' => $this->apellidoPaterno,
            'apellidoMaterno' => $this->apellidoMaterno,
            'genero' => $this->genero,
            'carrera' => $this->carrera,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'universidad' => $this->universidad,
            'fechaEntrada' => $this->fechaEntrada,
            'fechaSalida' => $this->fechaSalida,
            'fechaRegistro' => $this->fechaRegistro,
            'estadoID' => $this->estadoID,
            'nombreCompleto' => $this->getNombreCompleto()
        ];
    }
}