<?php

declare(strict_types=1);

namespace App\Seo;

/**
 * Guías para inquilinos y dueños. Son páginas de contenido propio, indexables aunque
 * todavía no haya avisos: le dan a Google (y a quien busca alquilar) algo útil que leer.
 *
 * Cada sección tiene párrafos de texto plano; los datos legales citan la norma original.
 */
final class Guias
{
    /** Fecha de la última revisión de los textos (se muestra en cada guía). */
    public const REVISADAS_EL = '2026-10-19';

    /** @return array<string, array<string, mixed>> */
    public static function todas(): array
    {
        return [
            'ley-de-alquileres' => [
                'h1' => 'Ley de alquileres 2026: qué rige hoy para alquilar en Río Gallegos',
                'title' => 'Ley de alquileres 2026: plazo, ajuste y depósito',
                'description' => 'Qué dice hoy la ley sobre alquileres: plazo, moneda, ajustes, depósito y cómo rescindir antes de tiempo. Explicado con el texto del DNU 70/2023.',
                'intro' => 'Desde fines de 2023 los alquileres ya no se rigen por la Ley 27.551, la "ley de alquileres" de 2020. El Decreto de Necesidad y Urgencia 70/2023 la derogó y cambió los artículos del Código Civil y Comercial que regulan las locaciones. La idea central es que casi todo lo pacta cada dueño con cada inquilino. Esto vale para todo el país, Río Gallegos incluida.',
                'secciones' => [
                    ['Plazo del contrato', [
                        'El plazo lo eligen las partes: puede ser de un año, de dos, de seis meses o el que acuerden. Ya no existe el mínimo obligatorio de tres años que fijaba la ley anterior.',
                        'Si el contrato no dice nada sobre el plazo, la ley completa el vacío: dos años para una vivienda permanente (con o sin muebles) y tres años para otros destinos, como un local. En un alquiler temporario, se aplica lo que sea costumbre en el lugar.',
                    ]],
                    ['Moneda y actualización del precio', [
                        'El alquiler se puede pactar en pesos o en dólares. Si el contrato dice dólares, el inquilino no puede exigir pagar en pesos.',
                        'Las partes también eligen cómo se actualiza el precio y cada cuánto: pueden usar cualquier índice, público o privado (por ejemplo el de inflación del INDEC), siempre que esté expresado en la misma moneda del alquiler. Antes de firmar, conviene hacer la cuenta de cuánto quedaría el alquiler después de cada ajuste.',
                    ]],
                    ['Depósito en garantía y forma de pago', [
                        'El monto y la moneda del depósito también son libres, igual que la forma en que se devuelve al terminar el contrato. Dejalo por escrito: cuánto se entrega, en qué moneda y cómo y cuándo se devuelve.',
                        'Las partes acuerdan también cada cuánto se paga: por mes, por trimestre o por períodos más largos, pero nunca en plazos menores a un mes. Si te piden pagar varios meses juntos por adelantado, es legal, pero queda a negociación.',
                    ]],
                    ['Irse antes de que termine el contrato', [
                        'El inquilino puede terminar el contrato en cualquier momento. Para hacerlo tiene que pagar una indemnización del 10% de lo que faltaría pagar de alquiler desde que avisa hasta la fecha de fin del contrato.',
                        'Por ejemplo: si faltan 10 meses de un alquiler de $500.000, lo que resta pagar son $5.000.000 y la indemnización sería de $500.000. Avisá siempre por un medio que deje constancia, como una carta documento.',
                    ]],
                    ['Contratos firmados antes del DNU', [
                        'Si tu contrato es anterior a la entrada en vigencia del DNU 70/2023 (fines de diciembre de 2023), puede seguir rigiéndose por las reglas con las que se firmó. Si tenés dudas sobre tu caso, consultá con un abogado o con el área de defensa del consumidor de la provincia.',
                    ]],
                ],
                'fuentes' => [
                    ['Decreto 70/2023, artículos 249 y 255 a 263 (Boletín Oficial, 21/12/2023)', 'https://www.boletinoficial.gob.ar/detalleAviso/primera/301122/20231221'],
                ],
            ],
            'requisitos-para-alquilar' => [
                'h1' => 'Requisitos para alquilar en Río Gallegos: qué te van a pedir',
                'title' => 'Requisitos para alquilar en Río Gallegos',
                'description' => 'Qué piden los dueños y las inmobiliarias de Río Gallegos para alquilar: ingresos, garantía, depósito y gastos de entrada. Cómo prepararte antes de ir a ver.',
                'intro' => 'Cuando aparece un buen alquiler en Río Gallegos, suele haber varios interesados. Tener los papeles listos antes de ir a verlo te puede dar ventaja. Como los requisitos no los fija la ley sino cada dueño o inmobiliaria, siempre preguntá qué piden antes de la visita.',
                'secciones' => [
                    ['Lo que te van a pedir casi siempre', [
                        'DNI y datos de contacto de quienes van a vivir en la propiedad.',
                        'Comprobantes de ingresos: los últimos recibos de sueldo si trabajás en relación de dependencia, o constancia de monotributo y facturación si sos independiente. Muchos dueños esperan que el alquiler no supere una parte razonable de los ingresos del hogar.',
                        'Una garantía, para asegurar el pago si algo sale mal.',
                    ]],
                    ['Tipos de garantía', [
                        'Garantía propietaria: alguien que tiene un inmueble a su nombre firma como garante. Es la que más se pide, pero no todos la tienen.',
                        'Seguro de caución: una aseguradora garantiza el contrato a cambio de un pago. Muchos dueños e inmobiliarias lo aceptan y evita depender de un familiar.',
                        'Garantes con recibo de sueldo: una o dos personas con ingresos comprobables que firman como codeudores.',
                        'Como la ley ya no fija un tipo de garantía obligatorio, se puede negociar. Preguntá cuáles acepta el dueño antes de conseguir una.',
                    ]],
                    ['Cuánta plata necesitás para entrar', [
                        'Calculá el primer mes de alquiler, el depósito en garantía (su monto se pacta libremente), las expensas si las hay, el costo de la garantía si es un seguro de caución y, si interviene una inmobiliaria, sus honorarios. Pedí que te digan el total por escrito antes de comprometerte.',
                        'Sumá también los gastos de mudanza y, si vas a cambiar la titularidad de los servicios, los trámites de gas, luz y agua.',
                    ]],
                    ['Antes de firmar', [
                        'Leé el contrato completo: plazo, precio, índice y frecuencia de ajuste, depósito, quién paga qué arreglos y cómo se rescinde. Lo explicamos en la guía de la ley de alquileres.',
                        'Hacé un inventario con fotos del estado de la propiedad el día que entrás, y que lo firmen las dos partes. Te va a servir para que te devuelvan el depósito al irte.',
                    ]],
                ],
                'fuentes' => [],
            ],
            'evitar-estafas-alquiler' => [
                'h1' => 'Cómo evitar estafas al buscar alquiler en Río Gallegos',
                'title' => 'Cómo evitar estafas con alquileres en Río Gallegos',
                'description' => 'Las estafas más comunes con avisos de alquiler y cómo detectarlas: señas por transferencia, precios demasiado bajos y dueños que "están de viaje".',
                'intro' => 'Cuando cuesta conseguir alquiler, aparecen los avisos falsos. La mayoría de las estafas siguen el mismo libreto: una propiedad linda a un precio bajo y un apuro para que transfieras plata antes de verla. Con unos pocos cuidados se evitan casi todas.',
                'secciones' => [
                    ['Señales de alerta', [
                        'Te piden una seña, una "reserva" o el depósito por transferencia antes de que puedas ver la propiedad.',
                        'El precio está muy por debajo de lo que piden por propiedades parecidas en el mismo barrio.',
                        'El supuesto dueño dice que está de viaje o que vive en otra provincia, y que te manda las llaves cuando pagues.',
                        'Las fotos parecen de otra ciudad o aparecen en otros avisos con otra dirección. Una búsqueda de la imagen en Google suele delatarlo.',
                        'Te apuran con que hay muchos interesados y tenés que decidir hoy.',
                    ]],
                    ['Qué hacer antes de pagar', [
                        'Visitá la propiedad en persona, o mandá a alguien de confianza si todavía no estás en Río Gallegos.',
                        'Pedí que te muestren que quien alquila es el dueño o está autorizado: el título, una boleta de impuesto a su nombre o la autorización por escrito si es una inmobiliaria.',
                        'No pagues nada sin un recibo o un contrato firmado, y desconfiá de quien solo acepta pagos a cuentas de terceros.',
                    ]],
                    ['Si un aviso de este sitio te parece sospechoso', [
                        'Cada aviso tiene un botón para denunciarlo. Lo revisamos y, si corresponde, lo damos de baja y bloqueamos al anunciante. Si ya fuiste víctima de una estafa, hacé la denuncia en la comisaría o en la fiscalía: guardá los chats, los comprobantes de transferencia y el aviso.',
                    ]],
                ],
                'fuentes' => [],
            ],
            'que-revisar-antes-de-alquilar' => [
                'h1' => 'Qué revisar al visitar un alquiler en Río Gallegos',
                'title' => 'Qué revisar al visitar un alquiler en Río Gallegos',
                'description' => 'Lista para la visita: calefacción y gas, viento y aberturas, humedad, consumos y expensas. Lo que más pesa al alquilar en el clima de Río Gallegos.',
                'intro' => 'En Río Gallegos, el invierno largo y el viento pesan en el día a día y en lo que se paga de gas y de luz. Una visita de quince minutos alcanza para revisar lo importante. Esta es la lista que conviene llevar.',
                'secciones' => [
                    ['Calefacción y gas', [
                        'Mirá qué calefacción tiene cada ambiente y si alcanza para toda la casa: calefactores, radiadores, losa radiante o estufas.',
                        'Los calefactores a gas tienen que tener salida de gases al exterior (tiro balanceado) y los ambientes, ventilación permanente. Un artefacto en mal estado puede producir monóxido de carbono, que no tiene olor y es mortal. Si ves manchas negras o la llama amarilla, pedí que lo revise un gasista matriculado antes de mudarte.',
                        'Preguntá si el gas es de red o envasado, y quién se hace cargo del mantenimiento de los artefactos.',
                    ]],
                    ['Viento, aberturas y humedad', [
                        'Cerrá puertas y ventanas y fijate si entra aire: con el viento de la zona, una abertura que no cierra bien se paga en frío y en gas.',
                        'Buscá manchas de humedad o moho en techos, rincones y placares, sobre todo en baños y dormitorios.',
                        'Revisá que las ventanas abran y cierren, y el estado de los vidrios y los burletes.',
                    ]],
                    ['Lo que vas a pagar además del alquiler', [
                        'Pedí ver las últimas facturas de gas y de luz, sobre todo las de invierno: son la mejor forma de estimar el gasto real.',
                        'Si es un departamento, preguntá cuánto son las expensas, qué incluyen y si el monto cambió hace poco.',
                        'Consultá si hay deudas de servicios o de impuestos, y a nombre de quién están las cuentas.',
                    ]],
                    ['Otros detalles', [
                        'Presión del agua y agua caliente: abrí la ducha y la canilla de la cocina a la vez.',
                        'Enchufes, tablero eléctrico y que la instalación tenga disyuntor.',
                        'Lugar para el auto y la distancia a lo que vas a usar todos los días: trabajo, escuela, colectivo y comercios.',
                        'Si tenés mascotas, que el contrato diga por escrito que se aceptan.',
                    ]],
                ],
                'fuentes' => [],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function porSlug(string $slug): ?array
    {
        return self::todas()[$slug] ?? null;
    }

    public static function url(string $slug): string
    {
        return '/guias/' . $slug;
    }
}
