<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 *@OA\Info(
 *    version="1.0.0",
 *    title="Zanichelli API Buzzer projects",
 *    description="REST APIs to get channels info",
 *    @OA\Contact(
 *         name="Zanichelli DEV team",
 *         email="developers@zanichelli.it"
 *    )
 *),
 * @OA\Components(
 *     @OA\Response(
 *         response="Success200",
 *         description="Operation successful",
 *         @OA\MediaType(
 *             mediaType="application/json")
 *     ),
 *     @OA\Response(
 *         response="Success201",
 *         description="Created",
 *         @OA\MediaType(
 *             mediaType="application/json")
 *     ),
 *     @OA\Response(
 *         response="Success204",
 *         description="No Content",
 *         @OA\MediaType(
 *             mediaType="application/json")
 *     ),
 *     @OA\Response(
 *         response="Error404",
 *         description="Not Found",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/Message404")
 *         )
 *     ),
 *     @OA\Response(
 *         response="Error409",
 *         description="Conflict",
 *         @OA\MediaType(
 *             mediaType="application/json")
 *     ),
 *     @OA\Response(
 *         response="Error422",
 *         description="Unprocessable entity: data validation error",
 *         @OA\MediaType(
 *             mediaType="application/json")
 *     ),
 *     @OA\Response(
 *         response="Error500",
 *         description="Internal Server Error",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/Message500")
 *         )
 *     ),
 *     @OA\Schema(
 *         schema="Message404",
 *         type="object",
 *         @OA\Property(
 *             property="message",
 *             type="string",
 *             default="Object not found"
 *         )
 *     ),
 *     @OA\Schema(
 *         schema="Message500",
 *         type="object",
 *         @OA\Property(
 *             property="message",
 *             type="string",
 *             default="System error"
 *         )
 *     ),
 * ),
 */

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    const PAGINATION = 12;
}
