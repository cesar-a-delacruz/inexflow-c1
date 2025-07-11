<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Entities\Business;
use App\Models\{BusinessModel, UserModel};
use App\Validation\Validators\BusinessValidator;
use Ramsey\Uuid\Uuid;

class BusinessController extends BaseController
{   
    protected BusinessModel $model;
    protected UserModel $user_model;
    protected BusinessValidator $form_validator;
    public function __construct() {
        $this->model = new BusinessModel();
        $this->user_model = new UserModel();
        $this->form_validator = new BusinessValidator();

        helper('form');
        helper('session');
    }

    public function new()
    {
        $current_page = session()->get('current_page');
        if (is_admin() && $current_page) return redirect()->to($current_page);

        if (!user_logged()) return redirect()->to('/');
        else session()->set('current_page', 'user/business/new');
        $session_id = session()->get('id');

        $data['title'] = 'Nuevo Negocio';
        $data['user'] = $this->user_model->find(uuid_to_bytes($session_id));
        return view('Business/new', $data);
    }
    public function show()
    {
        $current_page = session()->get('current_page');
        if (is_admin() && $current_page) return redirect()->to($current_page);

        if (!user_logged()) return redirect()->to('/');
        else session()->set('current_page', 'user/business');
        $session_id = session()->get('id');

        $data['title'] = 'Información del Negocio';
        $user_business = $this->user_model->find(uuid_to_bytes($session_id))->business_id;
        if (!$user_business) {
            return redirect()->to('user/business/new');
        }
        $data['business'] = $this->model->find(uuid_to_bytes($user_business));
        return view('Business/show', $data);
    }
    
    public function create()
    {
        $post = (object) $this->request->getPost(
            ['name', 'phone']
        );
        if (!$this->validate($this->form_validator->newRules())) {
            return redirect()->back()->withInput();
        }

        $business_id = Uuid::uuid3(Uuid::NAMESPACE_URL, strval(($this->model->getBusinessStats()['total'] + 1)));
        $session_id = session()->get('id');
        $this->model->createBusiness(new Business([
            'id' => $business_id,
            'name' => $post->name,
            'phone' => $post->phone,
            'registered_by'=> uuid_to_bytes($session_id),
        ]));

        $this->user_model->update(
            uuid_to_bytes($session_id), ['business_id' => uuid_to_bytes($business_id)]
        );
        return redirect()->to('user');
    }
    public function update()
    {
        $post = $this->request->getPost(
            ['name', 'phone', 'id']
        );
        $row = [];
        foreach ($post as $key => $value) {
            if ($key == 'id') continue;
            if ($value) $row[$key] = $value;
        }
        if (empty($row)) return redirect()->to('user/business');
        $row['id'] = $this->request->getPost('id');
        $business = new Business($row);
        if (!$this->validate($this->form_validator->showRules())) {
            return redirect()->back()->withInput();
        }
        $this->model->updateBusiness($business->id, $business);
        return redirect()->to('user/business');
    }
}
