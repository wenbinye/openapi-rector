<?php
use OpenApi\Annotations as OA;
class FooController {
    /**
     * @OA\Get(
     *     path="/requests",
     *     x={"query-args-$ref"="#/components/schemas/TransferRequestCriteria"},
     *     @OA\Response(
     *       response="200",
     *       description="查询成功",
     *       @OA\JsonContent(
     *         @OA\Property(property="total", type="integer", description="记录条数"),
     *         @OA\Property(property="list", type="array", @OA\Items(ref="#/components/schemas/TransferRequest"))
     *       )
     *    )
     * )
     */
    public function index(): void
    {

    }
}
?>
-----
<?php
use OpenApi\Annotations as OA;
class FooController
{
    /**
     * @OA\Get(
     *     path="/requests",
     *     x={"query-args-$ref"="#/components/schemas/TransferRequestCriteria"},
     *     @OA\Response(
     *       response="200",
     *       description="查询成功",
     *       @OA\JsonContent(
     *         @OA\Property(property="total", type="integer", description="记录条数"),
     *         @OA\Property(property="list", type="array", @OA\Items(ref="#/components/schemas/TransferRequest"))
     *       )
     *    )
     * )
     */
    public function index(): void
    {

    }
}
?>