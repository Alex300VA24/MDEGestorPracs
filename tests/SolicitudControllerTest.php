<?php
use PHPUnit\Framework\TestCase;
use App\Controllers\SolicitudController;

class SolicitudControllerTest extends TestCase {
    private function setServiceMock(SolicitudController $controller, $mock) {
        $ref = new ReflectionClass($controller);
        $prop = $ref->getProperty('service');
        $prop->setAccessible(true);
        $prop->setValue($controller, $mock);
    }

    public function testSubirDocumentoTipoNoValido() {
        $_POST = ['solicitudID' => 1, 'tipoDocumento' => 'invalido'];
        $_FILES = [
            'archivoDocumento' => [
                'tmp_name' => tempnam(sys_get_temp_dir(), 'doc'),
                'size' => 100,
                'error' => UPLOAD_ERR_OK
            ]
        ];
        file_put_contents($_FILES['archivoDocumento']['tmp_name'], 'dummy');

        $mockService = new class {
            public function subirDocumento() { return true; }
        };
        $controller = new SolicitudController($mockService);

        ob_start();
        $controller->subirDocumento();
        $output = ob_get_clean();
        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('error', $json);
        $this->assertStringContainsString('Tipo de documento no válido', $json['error']);
    }

    public function testSubirDocumentoExcedeTamano() {
        $_POST = ['solicitudID' => 1, 'tipoDocumento' => 'cv'];
        $_FILES = [
            'archivoDocumento' => [
                'tmp_name' => tempnam(sys_get_temp_dir(), 'doc'),
                'size' => 6 * 1024 * 1024,
                'error' => UPLOAD_ERR_OK
            ]
        ];
        file_put_contents($_FILES['archivoDocumento']['tmp_name'], str_repeat('A', 1024));

        $mockService = new class {
            public function subirDocumento() { return true; }
        };
        $controller = new SolicitudController($mockService);

        ob_start();
        $controller->subirDocumento();
        $output = ob_get_clean();
        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('error', $json);
        $this->assertStringContainsString('excede el tamaño permitido', $json['error']);
    }

    public function testSubirDocumentoMimeNoPermitido() {
        $_POST = ['solicitudID' => 1, 'tipoDocumento' => 'cv'];
        $_FILES = [
            'archivoDocumento' => [
                'tmp_name' => tempnam(sys_get_temp_dir(), 'doc'),
                'size' => 1024,
                'error' => UPLOAD_ERR_OK
            ]
        ];
        file_put_contents($_FILES['archivoDocumento']['tmp_name'], 'texto plano');

        $mockService = new class {
            public function subirDocumento() { return true; }
        };
        $controller = new SolicitudController($mockService);

        ob_start();
        $controller->subirDocumento();
        $output = ob_get_clean();
        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('error', $json);
        $this->assertStringContainsString('Tipo de archivo no permitido', $json['error']);
    }

    public function testSubirDocumentoValidoPdf() {
        $_POST = ['solicitudID' => 1, 'tipoDocumento' => 'cv', 'observacionesDoc' => 'ok'];
        $_FILES = [
            'archivoDocumento' => [
                'tmp_name' => tempnam(sys_get_temp_dir(), 'doc'),
                'size' => 2048,
                'error' => UPLOAD_ERR_OK
            ]
        ];
        file_put_contents($_FILES['archivoDocumento']['tmp_name'], "%PDF-1.4\n%âãÏÓ\n");

        $mockService = new class {
            public function subirDocumento($solicitudID, $tipoSP, $contenido, $observaciones) { return true; }
        };
        $controller = new SolicitudController($mockService);

        ob_start();
        $controller->subirDocumento();
        $output = ob_get_clean();
        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('success', $json);
        $this->assertTrue($json['success']);
    }
}
