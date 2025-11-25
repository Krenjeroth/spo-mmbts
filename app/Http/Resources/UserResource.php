<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $user_roles_permissions = $this?->loadMissing('roles.permissions');

        $permissions = [];
        $roles = [];

        if($user_roles_permissions) {
          foreach($user_roles_permissions->roles as $role) {
            $roles[] = [
              'id' => $role->id,
              'title' => $role->title,
            ];

            foreach($role->permissions as $singlePermission) {
              $permissions[] = $singlePermission->title;
            }
          }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $roles,
            'permissions' => collect($permissions)->unique()->map(function($permission) {
              return [
                $permission => true
              ];
            })->collapse()->toArray(),

            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
