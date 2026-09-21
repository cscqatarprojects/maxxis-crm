<?php

namespace Webkul\WebForm\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Lead\Models\TypeProxy;
use Webkul\WebForm\Contracts\WebForm as WebFormContract;

class WebForm extends Model implements WebFormContract
{
    protected $fillable = [
        'form_id',
        'title',
        'description',
        'submit_button_label',
        'submit_success_action',
        'submit_success_content',
        'create_lead',
        'lead_type_id',
        'background_color',
        'form_background_color',
        'form_title_color',
        'form_submit_button_color',
        'attribute_label_color',
    ];

    /**
     * The lead type applied to leads created from this form.
     */
    public function leadType()
    {
        return $this->belongsTo(TypeProxy::modelClass(), 'lead_type_id');
    }

    /**
     * The attributes that belong to the activity.
     */
    public function attributes()
    {
        return $this->hasMany(WebFormAttributeProxy::modelClass());
    }
}
