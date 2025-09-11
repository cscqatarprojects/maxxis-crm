# Maxxiss CRM - Chatbot Integration Guide

## Overview
This guide provides a complete solution for integrating a chatbot with the Maxxiss CRM system through webhooks, enabling automatic lead creation from customer inquiries.

## API Endpoints

### 1. Primary Lead Creation Endpoint
- **URL**: `https://maxxis.dev2.prodevr.com/api/v1/leads`
- **Method**: POST
- **Content-Type**: application/json

### 2. Webhook Endpoint (Alternative)
- **URL**: `https://maxxis.dev2.prodevr.com/webhook/chatbot/lead`
- **Method**: POST
- **Content-Type**: application/json

### 3. Health Check Endpoints
- **API Health**: `https://maxxis.dev2.prodevr.com/api/v1/health`
- **Webhook Health**: `https://maxxis.dev2.prodevr.com/webhook/health`

## Payload Structure

### Required Fields
```json
{
  "title": "Tire Inquiry - Toyota Corolla",
  "contact_name": "John Doe"
}
```

### Optional Fields
```json
{
  "car_make": "Toyota",
  "car_model": "Corolla", 
  "car_year": "2020",
  "car_type": "Sedan",
  "tire_size": "205/55R16",
  "lead_type": "New Business",
  "lead_source": "Chatbot",
  "description": "Tire inquiry for Toyota Corolla 2020 - Size: 205/55R16",
  "expected_close_date": "2024-12-31",
  "lead_value": 500,
  "sales_owner": "test@test.com"
}
```

## Response Format

### Success Response (201 Created)
```json
{
  "success": true,
  "message": "Lead created successfully in CRM",
  "data": {
    "lead_id": 123,
    "title": "Tire Inquiry - Toyota Corolla",
    "person_name": "John Doe",
    "status": "created",
    "created_at": "2024-08-13 14:33:55"
  }
}
```

### Error Response (422 Validation Error)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."],
    "contact_name": ["The contact name field is required."]
  }
}
```

### Error Response (500 Server Error)
```json
{
  "success": false,
  "message": "Failed to create lead in CRM",
  "error": "Database connection failed"
}
```

## Implementation Examples

### JavaScript/Node.js Example
```javascript
const axios = require('axios');

async function createLead(leadData) {
  try {
    const response = await axios.post('https://maxxis.dev2.prodevr.com/api/v1/leads', {
      title: leadData.title,
      contact_name: leadData.contactName,
      car_make: leadData.carMake,
      car_model: leadData.carModel,
      car_year: leadData.carYear,
      car_type: leadData.carType,
      tire_size: leadData.tireSize,
      lead_type: 'New Business',
      lead_source: 'Chatbot',
      description: leadData.description,
      expected_close_date: leadData.expectedCloseDate,
      lead_value: leadData.leadValue,
      sales_owner: leadData.salesOwner
    }, {
      headers: {
        'Content-Type': 'application/json'
      }
    });

    console.log('Lead created successfully:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error creating lead:', error.response?.data || error.message);
    throw error;
  }
}

// Usage
const leadData = {
  title: "Tire Inquiry - Honda Civic",
  contactName: "Jane Smith",
  carMake: "Honda",
  carModel: "Civic",
  carYear: "2019",
  carType: "Sedan",
  tireSize: "215/60R16",
  description: "Customer looking for winter tires",
  expectedCloseDate: "2024-12-31",
  leadValue: 600,
  salesOwner: "sales@maxxis.com"
};

createLead(leadData);
```

### Python Example
```python
import requests
import json

def create_lead(lead_data):
    url = "https://maxxis.dev2.prodevr.com/api/v1/leads"
    
    payload = {
        "title": lead_data["title"],
        "contact_name": lead_data["contact_name"],
        "car_make": lead_data.get("car_make"),
        "car_model": lead_data.get("car_model"),
        "car_year": lead_data.get("car_year"),
        "car_type": lead_data.get("car_type"),
        "tire_size": lead_data.get("tire_size"),
        "lead_type": "New Business",
        "lead_source": "Chatbot",
        "description": lead_data.get("description"),
        "expected_close_date": lead_data.get("expected_close_date"),
        "lead_value": lead_data.get("lead_value"),
        "sales_owner": lead_data.get("sales_owner")
    }
    
    headers = {
        'Content-Type': 'application/json'
    }
    
    try:
        response = requests.post(url, json=payload, headers=headers)
        response.raise_for_status()
        return response.json()
    except requests.exceptions.RequestException as e:
        print(f"Error creating lead: {e}")
        if hasattr(e, 'response') and e.response is not None:
            print(f"Response: {e.response.text}")
        raise

# Usage
lead_data = {
    "title": "Tire Inquiry - BMW X5",
    "contact_name": "Mike Johnson",
    "car_make": "BMW",
    "car_model": "X5",
    "car_year": "2021",
    "car_type": "SUV",
    "tire_size": "275/45R20",
    "description": "Customer needs all-season tires",
    "expected_close_date": "2024-12-31",
    "lead_value": 800,
    "sales_owner": "sales@maxxis.com"
}

result = create_lead(lead_data)
print(f"Lead created: {result}")
```

### PHP Example
```php
<?php

function createLead($leadData) {
    $url = 'https://maxxis.dev2.prodevr.com/api/v1/leads';
    
    $payload = [
        'title' => $leadData['title'],
        'contact_name' => $leadData['contact_name'],
        'car_make' => $leadData['car_make'] ?? null,
        'car_model' => $leadData['car_model'] ?? null,
        'car_year' => $leadData['car_year'] ?? null,
        'car_type' => $leadData['car_type'] ?? null,
        'tire_size' => $leadData['tire_size'] ?? null,
        'lead_type' => 'New Business',
        'lead_source' => 'Chatbot',
        'description' => $leadData['description'] ?? null,
        'expected_close_date' => $leadData['expected_close_date'] ?? null,
        'lead_value' => $leadData['lead_value'] ?? null,
        'sales_owner' => $leadData['sales_owner'] ?? null
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 201) {
        return json_decode($response, true);
    } else {
        throw new Exception("Error creating lead: " . $response);
    }
}

// Usage
$leadData = [
    'title' => 'Tire Inquiry - Mercedes C-Class',
    'contact_name' => 'Sarah Wilson',
    'car_make' => 'Mercedes',
    'car_model' => 'C-Class',
    'car_year' => '2022',
    'car_type' => 'Sedan',
    'tire_size' => '225/45R18',
    'description' => 'Customer needs performance tires',
    'expected_close_date' => '2024-12-31',
    'lead_value' => 700,
    'sales_owner' => 'sales@maxxis.com'
];

try {
    $result = createLead($leadData);
    echo "Lead created successfully: " . json_encode($result);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
```

## Testing the Integration

### 1. Health Check Test
```bash
curl -X GET https://maxxis.dev2.prodevr.com/api/v1/health
```

### 2. Lead Creation Test
```bash
curl -X POST https://maxxis.dev2.prodevr.com/api/v1/leads \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Lead - API Integration",
    "contact_name": "Test User",
    "car_make": "Toyota",
    "car_model": "Camry",
    "car_year": "2020",
    "car_type": "Sedan",
    "tire_size": "215/60R16",
    "lead_type": "New Business",
    "lead_source": "Chatbot",
    "description": "Test lead for API integration",
    "expected_close_date": "2024-12-31",
    "lead_value": 500,
    "sales_owner": "admin@example.com"
  }'
```

## Error Handling

### Common Error Scenarios
1. **Validation Errors (422)**: Missing required fields or invalid data format
2. **Server Errors (500)**: Database issues, missing dependencies, or internal errors
3. **Network Errors**: Connection timeouts, DNS resolution issues

### Recommended Error Handling
1. Implement retry logic with exponential backoff
2. Log all API calls and responses for debugging
3. Provide user-friendly error messages
4. Implement circuit breaker pattern for reliability

## Security Considerations

1. **Rate Limiting**: The API implements rate limiting to prevent abuse
2. **Input Validation**: All inputs are validated and sanitized
3. **Logging**: All requests are logged for audit purposes
4. **Error Handling**: Sensitive information is not exposed in error messages

## Monitoring and Logging

The integration includes comprehensive logging:
- All webhook requests are logged with headers and payload
- Lead creation attempts are logged with success/failure status
- Errors are logged with full stack traces for debugging
- Performance metrics are tracked for monitoring

## Troubleshooting

### Common Issues
1. **Installation Required**: Ensure the CRM is properly installed
2. **Database Connection**: Verify database configuration
3. **Missing Dependencies**: Run `composer install` if needed
4. **Route Caching**: Clear route cache with `php artisan route:clear`

### Debug Steps
1. Check server logs: `tail -f storage/logs/laravel.log`
2. Test health endpoints first
3. Verify database connectivity
4. Check API route registration: `php artisan route:list`

## Support

For technical support or questions about this integration:
- Check the logs for detailed error information
- Verify all required fields are provided
- Ensure the CRM system is properly configured
- Contact the development team for assistance
