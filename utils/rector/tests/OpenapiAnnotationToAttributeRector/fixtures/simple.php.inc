<?php

namespace App;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   version="1.0.0",
 *   title="My API",
 *   @OA\License(name="MIT"),
 *   @OA\Attachable()
 * )
 */
class OpenApiSpec
{
}
?>
-----
<?php

namespace App;

use OpenApi\Attributes as OA;
#[OA\Info(
    version: "1.0.0",
    title: "My API",
    license: new OA\License(name: "MIT"),
    attachables: [new OA\Attachable()]
)]
class OpenApiSpec
{
}
?>
