<?php

use App\Http\Requests\Api\V1\Brand\ForceDeactivateSeatsRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

describe('ForceDeactivateSeatsRequest', function () {
    it('authorizes all users', function () {
        $request = new ForceDeactivateSeatsRequest();

        expect($request->authorize())->toBeTrue();
    });

    it('has correct validation rules', function () {
        $request = new ForceDeactivateSeatsRequest();

        $rules = $request->rules();

        expect($rules)->toHaveKey('reason');
        expect($rules['reason'])->toContain('nullable');
        expect($rules['reason'])->toContain('string');
        expect($rules['reason'])->toContain('max:500');
    });

    it('has custom validation messages', function () {
        $request = new ForceDeactivateSeatsRequest();

        $messages = $request->messages();

        expect($messages)->toHaveKey('reason.max');
        expect($messages['reason.max'])->toBe('The reason cannot exceed 500 characters.');
    });

    it('passes validation with valid reason', function () {
        $request = new ForceDeactivateSeatsRequest();
        $request->merge(['reason' => 'Valid administrative reason']);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeFalse();
    });

    it('passes validation with no reason', function () {
        $request = new ForceDeactivateSeatsRequest();

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeFalse();
    });

    it('fails validation with reason exceeding 500 characters', function () {
        $request = new ForceDeactivateSeatsRequest();
        $request->merge(['reason' => str_repeat('a', 501)]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('reason'))->toBeTrue();
    });

    it('fails validation with non-string reason', function () {
        $request = new ForceDeactivateSeatsRequest();
        $request->merge(['reason' => 123]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('reason'))->toBeTrue();
    });
});
