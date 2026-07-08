<?php

declare(strict_types=1);

namespace App\Modules\Product\Presentation\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Modules\Product\Application\Services\AttributeService;

class AttributeController
{
    private AttributeService $attributeService;

    public function __construct()
    {
        $this->attributeService = new AttributeService();
    }

    public function index(Request $request): Response
    {
        $attributes = $this->attributeService->getAllWithValues();

        return Response::make(view('Product::attributes.index', ['attributes' => $attributes]));
    }

    public function storeAttribute(Request $request): Response
    {
        try {
            $this->attributeService->createAttribute((string) $request->input('name'));
        } catch (ValidationException $e) {
            Session::flash('error', implode(' ', $e->errors()));

            return Response::redirect('/admin/attributes');
        }

        Session::flash('success', 'Atribut berhasil ditambahkan.');

        return Response::redirect('/admin/attributes');
    }

    public function storeValue(Request $request): Response
    {
        $attributeId = (int) $request->input('attribute_id');

        try {
            $this->attributeService->createValue($attributeId, (string) $request->input('value'));
        } catch (ValidationException $e) {
            Session::flash('error', implode(' ', $e->errors()));

            return Response::redirect('/admin/attributes');
        }

        Session::flash('success', 'Value atribut berhasil ditambahkan.');

        return Response::redirect('/admin/attributes');
    }
}