<?php

namespace Tests\Unit;

use App\Support\Redactor;
use PHPUnit\Framework\TestCase;

class RedactorTest extends TestCase
{
    public function test_enmascara_conservando_solo_los_ultimos_caracteres(): void
    {
        $this->assertSame('********567', Redactor::enmascarar('59171234567'));
    }

    public function test_enmascara_por_completo_si_el_valor_es_mas_corto_que_lo_visible(): void
    {
        $this->assertSame('**', Redactor::enmascarar('ab'));
    }

    public function test_valor_vacio_no_revienta_y_no_deja_nada_reconocible(): void
    {
        $this->assertSame('(vacío)', Redactor::enmascarar(''));
    }

    public function test_identificar_para_log_prefiere_el_id_del_cliente_si_esta_disponible(): void
    {
        $this->assertSame('cliente #42', Redactor::identificarParaLog('42', '59171234567'));
    }

    public function test_identificar_para_log_enmascara_si_no_hay_id_de_cliente(): void
    {
        $identificador = Redactor::identificarParaLog(null, '59171234567');

        $this->assertStringNotContainsString('59171234567', $identificador);
        $this->assertSame('********567', $identificador);
    }

    public function test_identificar_para_log_enmascara_si_el_id_de_cliente_viene_vacio(): void
    {
        $identificador = Redactor::identificarParaLog('', 'correo@ejemplo.com');

        $this->assertStringNotContainsString('correo@ejemplo.com', $identificador);
    }
}
