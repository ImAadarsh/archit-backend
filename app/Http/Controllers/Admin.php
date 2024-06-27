<?php

namespace App\Http\Controllers;

use App\Models\Addres;
use App\Models\Businesss;
use App\Models\Expenses;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Locations;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class Admin extends Controller
{
    public function insertBusiness(Request $request)
    {
        $rules = [
            'business_name' => 'required',
            'gst' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'alternate_phone' => 'nullable',
            'owner_name' => 'required',
        ];
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return $validator->errors();
        }
        try {
            $business = new Businesss();
            $business->business_name = $request->business_name;
            $business->gst = $request->gst;
            $business->email = $request->email;
            $business->phone = $request->phone;
            $business->alternate_phone = $request->input('alternate_phone');
            $business->owner_name = $request->owner_name;
            if ($request->hasFile('logo')) {
                $file = $request->file('logo')->store('public/logo');
                $business->logo = $file;
            }
            $business->save();
            return response([
                'status' => true,
                'message' => 'Business created successfully.',
                'data' => $business
            ], 200);
        } catch (\Exception $e) {
            return response([
                'status' => false,
                'message' => 'Failed to insert business.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function insertLocation(Request $request)
{
    $rules = [
        'location_name' => 'required',
        'business_id' => 'required',
        'address' => 'required',
        'email' => 'required|email',
        'alternate_phone' => 'nullable',
        'phone' => 'required',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return $validator->errors();
    }

    try {
        $location = new Locations();
        $location->location_name = $request->location_name;
        $location->business_id = $request->business_id;
        $location->address = $request->address;
        $location->email = $request->email;
        $location->alternate_phone = $request->input('alternate_phone');
        $location->phone = $request->phone;
        $location->save();
        return response([
            'status' => true,
            'message' => 'Location created successfully.',
            'data' => $location
        ], 200);
    } catch (\Exception $e) {
        return response([
            'status' => false,
            'message' => 'Failed to insert location.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function createInvoice(Request $request){
    // Validation rules for invoice creation
    $rules = [
        'type' => 'required',
        // Add validation rules for other fields if necessary
    ];

    // Validate the incoming request
    $validator = Validator::make($request->all(), $rules);

    // If validation fails, return errors
    if ($validator->fails()) {
        return response()->json(['status' => false, 'errors' => $validator->errors()], 400);
    }

    try {
        // Get the last stored serial number only if type is not 'performa'
        if ($request->type != 'performa') {
            $lastSerialNo = Invoice::orderBy('serial_no', 'desc')->value('serial_no');

            // Increment the serial number by 1
            $nextSerialNo = $lastSerialNo + 1;
        } else {
            $nextSerialNo = null; // No serial number consumed for 'performa'
        }

        $invoice = new Invoice();
        $invoice->serial_no = $nextSerialNo;
        $invoice->name = $request->name;
        $invoice->mobile_number = $request->mobile_number;
        $invoice->customer_type = $request->customer_type;
        $invoice->doc_type = $request->doc_type;
        $invoice->doc_no = $request->doc_no;
        $invoice->business_id = $request->business_id;
        $invoice->location_id = $request->location_id;
        $invoice->payment_mode = $request->payment_mode;
        $invoice->billing_address_id = $request->billing_address_id;
        $invoice->shipping_address_id = $request->shipping_address_id;
        $invoice->type = $request->type;
        $invoice->is_completed = $request->is_completed;
        $invoice->invoice_date = $request->invoice_date;
        // Add other fields as necessary

        $invoice->save();

        return response()->json(['status' => true, 'message' => 'Invoice created successfully.', 'data' => $invoice], 201);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to create invoice.', 'error' => $e->getMessage()], 500);
    }
}
    public function getAllInvoices(Request $request){
    try {
        $query = Invoice::query();
        $query->orderBy('created_at', 'desc');

        // If location_id and business_id are provided, filter by both
        if ($request->has('location_id') && $request->has('business_id')) {
            $query->where('location_id', $request->location_id)
                  ->where('business_id', $request->business_id);
        }
        // If only business_id is provided, filter by business_id
        elseif ($request->has('business_id')) {
            $query->where('business_id', $request->business_id);
        }

        // Retrieve the invoices along with the total price
        $invoices = $query->withSum('items', 'price_of_all')->get();

        // Check if any invoices are found
        if ($invoices->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No invoices found.'], 404);
        }

        // Return the invoices
        return response()->json(['status' => true, 'message' => 'Invoices retrieved successfully.', 'data' => $invoices], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to retrieve invoices.', 'error' => $e->getMessage()], 500);
    }
}
    public function getDetailedInvoice($invoiceId){
    try {
        // Fetch the invoice along with its related items and product details
        $invoice = Invoice::with(['items.product', 'billingAddress', 'shippingAddress'])->find($invoiceId);

        // Check if the invoice is found
        if (!$invoice) {
            return response()->json(['status' => false, 'message' => 'Invoice not found.'], 404);
        }

        // Return the detailed invoice
        return response()->json(['status' => true, 'message' => 'Invoice details retrieved successfully.', 'data' => $invoice], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to retrieve invoice details.', 'error' => $e->getMessage()], 500);
    }
}
    public function removeInvoice($id){
    try {
        // Find the invoice by ID
        $invoice = Invoice::find($id);

        // If invoice not found, return error
        if (!$invoice) {
            return response()->json(['status' => false, 'message' => 'Invoice not found.'], 404);
        }

        // Delete the invoice
        $invoice->delete();

        return response()->json(['status' => true, 'message' => 'Invoice deleted successfully.'], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to delete invoice.', 'error' => $e->getMessage()], 500);
    }
}

    public function editInvoice(Request $request){
    // Validation rules for updating invoice
    $rules = [
        'id' => 'required',
        // Add validation rules for other fields if necessary
    ];

    // Validate the incoming request
    $validator = Validator::make($request->all(), $rules);

    // If validation fails, return errors
    if ($validator->fails()) {
        return response()->json(['status' => false, 'errors' => $validator->errors()], 400);
    }

    try {
        // Find the invoice by ID
        $invoice = Invoice::find($request->id);

        // If invoice not found, return error
        if (!$invoice) {
            return response()->json(['status' => false, 'message' => 'Invoice not found.'], 404);
        }

        // Update the invoice fields
        if(isset($request->name)){
            $invoice->name = $request->name;
        }
        if(isset($request->mobile_number)){
            $invoice->mobile_number = $request->mobile_number;
        }
        if(isset($request->customer_type)){
            $invoice->customer_type = $request->customer_type;
        }
        if(isset($request->doc_type)){
            $invoice->doc_type = $request->doc_type;
        }
        if(isset($request->doc_no)){
            $invoice->doc_no = $request->doc_no;
        }
        if(isset($request->business_id)){
            $invoice->business_id = $request->business_id;
        }
        if(isset($request->location_id)){
            $invoice->location_id = $request->location_id;
        }
        if(isset($request->payment_mode)){
            $invoice->payment_mode = $request->payment_mode;
        }
        if(isset($request->billing_address_id)){
            $invoice->billing_address_id = $request->billing_address_id;
        }
        if(isset($request->shipping_address_id)){
            $invoice->shipping_address_id = $request->shipping_address_id;
        }
        if(isset($request->is_completed)){
            $invoice->is_completed = $request->is_completed;
        }
        if(isset($request->invoice_date)){
            $invoice->invoice_date = $request->invoice_date;
        }
        
        // Update other fields as necessary
        $invoice->save();
        return response()->json(['status' => true, 'message' => 'Invoice updated successfully.', 'data' => $invoice], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to update invoice.', 'error' => $e->getMessage()], 500);
    }
}


    public function addProduct(Request $request){
    // Validation rules for invoice creation
    $rules = [
        'invoice_id' => 'required',
        'hsn_code' => 'required',
        'name' => 'required',
        'price' => 'required',
        'quantity' => 'required',
    ];

    // Validate the incoming request
    $validator = Validator::make($request->all(), $rules);

    // If validation fails, return errors
    if ($validator->fails()) {
        return response()->json(['status' => false, 'errors' => $validator->errors()], 400);
    }

    try {
        // Get the last stored serial number
        if(Product::where('hsn_code',$request->hsn_code)->first()){
            $product = Product::where('hsn_code',$request->hsn_code)->first();
        }else{
            $product = new Product();
            $product->name = $request->name;
            $product->hsn_code = $request->hsn_code;
            $product->save();
        }
        $invoice = Invoice::find($request->invoice_id);

        $item = new Item();
        $item->product_id = $product->id;
        $item->invoice_id = $request->invoice_id;
        $item->quantity = $request->quantity;
       if($invoice->type=="normal"){
           if($request->is_gst==1){
            $item->price_of_one = round((($request->price)/1.18),2);
        }else{
            $item->price_of_one = $request->price;
        }
       }else if($invoice->type=='performa'){
             $item->price_of_one = $request->price;
            
        }
       
        $address = Addres::where('invoice_id',$request->invoice_id)->first();
        // Calculate GST based on whether it's inclusive or exclusive
        if($invoice->type=="normal"){
             if ($request->is_gst == 1) {
            // Inclusive GST
            $item->is_gst = 1;
            // Check if the address place is Delhi
            if ($address->state == 'delhi' || $address->state == 'Delhi') {
                $item->dgst = round(((0.09 * $item->price_of_one)*$request->quantity),2); // 9% GST for Delhi
                $item->cgst = round(((0.09 * $item->price_of_one)*$request->quantity),2); // 9% GST for Delhi
                $item->igst = 0; // No IGST for Delhi
            } else {
                $item->dgst = 0; // No DGST for other states
                $item->cgst = 0; // No CGST for other states
                $item->igst = round(((0.18 * $item->price_of_one)*$request->quantity),2); // 18% IGST for other states
            }
        } else {
            // Exclusive GST
            $item->is_gst = 0;
            if ($address->state == 'delhi' || $address->state == 'Delhi') {
                $item->dgst = round(((0.09 * $item->price_of_one)*$request->quantity),2); // 9% GST for Delhi
                $item->cgst = round(((0.09 * $item->price_of_one)*$request->quantity),2); // 9% GST for Delhi
                $item->igst = 0; // No IGST for Delhi
            } else {
                $item->dgst = 0; // No DGST for other states
                $item->cgst = 0; // No CGST for other states
                $item->igst = round(((0.18 * $item->price_of_one)*$request->quantity),2); // 18% IGST for other states
            }
            // $item->price_of_all = $item->price_of_one*$request->quantity;
        }
            
        }else if($invoice->type=='performa'){
             $item->dgst = 0;
                $item->cgst = 0;
                $item->igst = 0;
            
        }
        
       
        $temp = $item->price_of_one*$request->quantity + $item->dgst + $item->cgst + $item->igst;
        $item->price_of_all = round(($temp),2);
        $item->save();
        // echo $request->invoice_id;
        $update_main = Invoice::where('id',$request->invoice_id)->first();
        // print_r($update_main);
        $update_main->total_dgst = $update_main->total_dgst+$item->dgst;
        $update_main->total_cgst = $update_main->total_cgst+$item->cgst;
        $update_main->total_igst = $update_main->total_igst+$item->igst;
        $update_main->total_amount = $update_main->total_amount + $item->price_of_all;
        $update_main->save();
        $item->t_dgst = round(($update_main->total_dgst),2);
         $item->t_cgst = round(($update_main->total_cgst),2);
          $item->t_igst = round(($update_main->total_igst),2);
           $item->total_amount = round(($update_main->total_amount),2);
           $item->total_ex_gst_amount = round(($update_main->total_amount - $update_main->total_dgst - $update_main->total_cgst - $update_main->total_igst),2);
         
        return response()->json(['status' => true, 'message' => 'Product Added successfully.', 'data' => $item,  ], 201);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to create invoice.', 'error' => $e->getMessage()], 500);
    }
}
    public function getItemsByInvoiceId(Request $request){
    // Validation rules for invoice ID
    $rules = [
        'invoice_id' => 'required|exists:invoices,id',
    ];

    // Validate the incoming request
    $validator = Validator::make($request->all(), $rules);

    // If validation fails, return errors
    if ($validator->fails()) {
        return response()->json(['status' => false, 'errors' => $validator->errors()], 400);
    }

    try {
        // Fetch items associated with the provided invoice ID
        $items = Item::where('invoice_id', $request->invoice_id)->get();

        // Check if any items are found
        if ($items->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No items found for the provided invoice ID.'], 404);
        }

        // Return the items
        return response()->json(['status' => true, 'message' => 'Items retrieved successfully.', 'data' => $items], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to retrieve items.', 'error' => $e->getMessage()], 500);
    }
}

    public function editProduct(Request $request){
    // Validation rules for updating item
    $rules = [
        'hsn_code' => 'required',
        'name' => 'required',
        'price' => 'required',
        'quantity' => 'required',
        'item_id' => 'required',
    ];

    // Validate the incoming request
    $validator = Validator::make($request->all(), $rules);

    // If validation fails, return errors
    if ($validator->fails()) {
        return response()->json(['status' => false, 'errors' => $validator->errors()], 400);
    }

    try {
        // Find the item by its ID
        $item = Item::findOrFail($request->item_id);

        // Find or create the product based on HSN code
        if ($product = Product::where('hsn_code', $request->hsn_code)->first()) {
            $item->product_id = $product->id;
        } else {
            $product = new Product();
            $product->name = $request->name;
            $product->hsn_code = $request->hsn_code;
            $product->save();
            $item->product_id = $product->id;
        }

        // Update item details
        $item->quantity = $request->quantity;
        if($request->is_gst==1){
            $item->price_of_one = $request->price;
        }else{
            $item->price_of_one = $request->price/1.18;
        }
        $address = Addres::where('invoice_id', $request->invoice_id)->first();
        // Calculate GST based on whether it's inclusive or exclusive
        if($request->type=='normal'){
                  if ($request->is_gst == 1) {
            // Inclusive GST
            // Check if the address place is Delhi
           
            if ($address->state == 'delhi') {
                $item->dgst = (0.09 * $item->price_of_one) * $request->quantity; // 9% GST for Delhi
                $item->cgst = (0.09 * $item->price_of_one) * $request->quantity; // 9% GST for Delhi
                $item->igst = 0; // No IGST for Delhi
            } else {
                $item->dgst = 0; // No DGST for other states
                $item->cgst = 0; // No CGST for other states
                $item->igst = (0.18 * $item->price_of_one) * $request->quantity; // 18% IGST for other states
            }
        } else {
            // Exclusive GST
            if ($address->state == 'delhi') {
                $item->dgst = (0.09 * $item->price_of_one) * $request->quantity; // 9% GST for Delhi
                $item->cgst = (0.09 * $item->price_of_one) * $request->quantity; // 9% GST for Delhi
                $item->igst = 0; // No IGST for Delhi
            } else {
                $item->dgst = 0; // No DGST for other states
                $item->cgst = 0; // No CGST for other states
                $item->igst = (0.18 * $item->price_of_one) * $request->quantity; // 18% IGST for other states
            }
        }
            
        }else if($request->type=='performa'){
             $item->dgst = 0;
                $item->cgst = 0;
                $item->igst = 0;
            
        }
  

        // Calculate total price of the item
        $item->price_of_all = $item->price_of_one * $request->quantity + $item->dgst + $item->cgst + $item->igst;
        $update_main = Invoice::find($request->invoice_id);
        $update_main->total_dgst = $update_main->total_dgst+$item->dgst;
        $update_main->total_cgst = $update_main->total_cgst+$item->cgst;
        $update_main->total_igst = $update_main->total_igst+$item->igst;
        $update_main->total_amount = $update_main->total_amount + $item->price_of_all;
        $update_main->save();
        // Save the updated item
        $item->save();
        
        return response()->json(['status' => true, 'message' => 'Item updated successfully.', 'data' => $item], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to update item.', 'error' => $e->getMessage()], 500);
    }
}

public function removeItem($item_id){
    try {
        // Find the item to remove
        $item = Item::findOrFail($item_id);
        
        if($item){
            $update_main = Invoice::find($item->invoice_id);

            if ($update_main) {
                // Update the main invoice totals
                $update_main->total_dgst -= $item->dgst;
                $update_main->total_cgst -= $item->cgst;
                $update_main->total_igst -= $item->igst;
                $update_main->total_amount -= $item->price_of_all;

                // Save the updated invoice
                $update_main->save();

                // Create response data with rounded values
                $item1 = new \stdClass();
                $item1->t_dgst = round($update_main->total_dgst, 2);
                $item1->t_cgst = round($update_main->total_cgst, 2);
                $item1->t_igst = round($update_main->total_igst, 2);
                $item1->total_amount = round($update_main->total_amount, 2);
                $item1->total_ex_gst_amount = round($update_main->total_amount - $update_main->total_dgst - $update_main->total_cgst - $update_main->total_igst, 2);
            } else {
                return response()->json(['status' => false, 'message' => 'Invoice not found.'], 404);
            }
        }

        // Delete the item
        $item->delete();
        
        return response()->json(['status' => true, 'message' => 'Item removed successfully.', 'data' => $item1], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to remove item.', 'error' => $e->getMessage()], 500);
    }
}

    public function addAddress(Request $request){
    $rules = [
        'state' => 'required',
        'invoice_id' => 'required',
        'type' => 'required',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return $validator->errors();
    }

    try {
        if(Addres::where('invoice_id', $request->invoice_id)->where('type',$request->type)->first()){
            $location = Addres::where('invoice_id', $request->invoice_id)->where('type',$request->type)->first();
            $location->type = $request->type;
            $location->address_1 = $request->address_1;
            $location->address_2 = $request->address_2;
            $location->city = $request->city;
            $location->state = $request->state;
            $location->pincode = $request->pincode;
            $location->save();
        }else{
            $location = new Addres();
            $location->invoice_id = $request->invoice_id;
            $location->type = $request->type;
            $location->address_1 = $request->address_1;
            $location->address_2 = $request->address_2;
            $location->city = $request->city;
            $location->state = $request->state;
            $location->pincode = $request->pincode;
            $location->save();
             
        }
        $update_main = Invoice::find($request->invoice_id);
             if($request->type==0){
                 $update_main->billing_address_id = $location->id;
             }else{
                  $update_main->shipping_address_id = $location->id;
             }
             $update_main->save();
        
        
        return response([
            'status' => true,
            'message' => 'Adress created/Updated successfully.',
            'data' => $location
        ], 200);
    } catch (\Exception $e) {
        return response([
            'status' => false,
            'message' => 'Failed to insert location.',
            'error' => $e->getMessage()
        ], 500);
    }
}
private function getFileExtension($base64Data) {
    $fileInfo = explode(';base64,', $base64Data);
    $mime = str_replace('data:', '', $fileInfo[0]);
    $extension = explode('/', $mime)[1];
    return $extension;
}
public function addExpense(Request $request){
    $rules = [
        'name' => 'required',
        'amount' => 'required',
        'type' => 'required',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return $validator->errors();
    }

    try {
        if($expense = Expenses::find($request->id)){
            $expense->name = $request->name;
            $expense->amount = $request->amount;
            $expense->type = $request->type;
            $expense->business_id = $request->business_id;
            $expense->location_id = $request->location_id;
            $expense->user_id = $request->user_id;
            $expense->save();
            $expense->find($expense->id);
            if ($request->has('file')) {
                $fileData = $request->file; // base64 encoded file data
                $fileName = 'expense_' . time() . '.' . $this->getFileExtension($fileData).'.'.$request->extension;
                $filePath = 'public/expense/' . $fileName;
                \Storage::put($filePath, base64_decode($fileData));
                $expense->file = $filePath;
            }
    
            $expense->save();
        } else {
            $expense = new Expenses();
            $expense->name = $request->name;
            $expense->amount = $request->amount;
            $expense->type = $request->type;
            $expense->save();
            $expense->find($expense->id);
            if ($request->has('file')) {
                $fileData = $request->file; // base64 encoded file data
                $fileName = 'expense_'.$expense->id.'.'.$request->extension;
                $filePath = 'public/expense/' . $fileName;
                \Storage::put($filePath, base64_decode($fileData));
                $expense->file = $filePath;
            }
            $expense->save();
        }
        
        return response([
            'status' => true,
            'message' => 'Expense created/Updated successfully.',
            'data' => $expense
        ], 200);
    } catch (\Exception $e) {
        return response([
            'status' => false,
            'message' => 'Failed to insert expense.',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function getAllExpenses(Request $request){
    $query = Expenses::query();

    // Filter by day
    if($request->has('day')){
        $query->whereDate('created_at', $request->day);
    }

    // Filter by month
    if($request->has('month')){
        $query->whereMonth('created_at', $request->month);
    }

    // Filter by week
    if($request->has('week_start')){
        // Assuming the week is passed as an array with start and end dates
        $query->whereBetween('created_at', [$request->week_start, $request->week_end]);
    }

    // Filter by year
    if($request->has('year')){
        $query->whereYear('created_at', $request->year);
    }

    // Filter by expense type (0 or 1)
    if($request->has('type')){
        $type = $request->type;
        $query->where('type', $type);
    }

        // Filter by name
        if($request->has('name')){
            $name = $request->name;
            $query->where('name', 'like', '%' . $name . '%');
        }

            // Filter by amount range
    if($request->has('amount_min') && $request->has('amount_max')){
        $amountMin = $request->amount_min;
        $amountMax = $request->amount_max;
        $query->whereBetween('amount', [$amountMin, $amountMax]);
    } 

    if($request->has('expense_id')){
        $query->where('id', $request->expense_id);
    }

        // Order by id in descending order
        $query->orderBy('id', 'DESC');


    $expenses = $query->get();

    return response([
        'status' => true,
        'data' => $expenses,
        'message' => "Data Feteched."
    ], 200);
}


// Get expense by ID
public function getExpenseById($id){
    $expense = Expenses::find($id);
    if($expense){
        return response([
            'status' => true,
            'data' => $expense
        ], 200);
    }else{
        return response([
            'status' => false,
            'message' => 'Expense not found.'
        ], 404);
    }
}

// Delete expense by ID
public function deleteExpense(Request $request){
    $expense = Expenses::find($request->id);
    if($expense){
        $expense->delete();
        return response([
            'status' => true,
            'message' => 'Expense deleted successfully.'
        ], 200);
    }else{
        return response([
            'status' => false,
            'message' => 'Expense not found.'
        ], 404);
    }
}

    public function getAddressByInvoiceId(Request $request){
    // Validation rules for invoice ID
    $rules = [
        'invoice_id' => 'required|exists:addres,invoice_id',
    ];

    // Validate the incoming request
    $validator = Validator::make($request->all(), $rules);

    // If validation fails, return errors
    if ($validator->fails()) {
        return response()->json(['status' => false, 'errors' => $validator->errors()], 400);
    }

    try {
        // Fetch the address associated with the provided invoice ID
        $address = Addres::where('invoice_id', $request->invoice_id)->get();

        // Check if the address is found
        if (!$address) {
            return response()->json(['status' => false, 'message' => 'No address found for the provided invoice ID.'], 404);
        }

        // Return the address
        return response()->json(['status' => true, 'message' => 'Address retrieved successfully.', 'data' => $address], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to retrieve address.', 'error' => $e->getMessage()], 500);
    }
}

public function getSaleReport(Request $request){
    $query = Invoice::query();

    // Filter by day
    if($request->has('day')){
        $query->whereDate('invoice_date', $request->day);
    }

    // Filter by month
    if($request->has('month')){
        $query->whereMonth('invoice_date', $request->month);
    }

    // Filter by week
    if($request->has('week_start')){
        // Assuming the week is passed as an array with start and end dates
        $query->whereBetween('invoice_date', [$request->week_start, $request->week_end]);
    }

    // Filter by year
    if($request->has('year')){
        $query->whereYear('invoice_date', $request->year);
    }

    // Filter by invoice type or performa
    if($request->has('type')){
        $Type = $request->type;
        $query->where('type', $Type);
    }

    // Filter by name
    if($request->has('name')){
        $name = $request->name;
        $query->where('name', 'like', '%' . $name . '%');
    }

    // Filter by amount range
    if($request->has('amount_min') && $request->has('amount_max')){
        $amountMin = $request->amount_min;
        $amountMax = $request->amount_max;
        $query->whereBetween('total_amount', [$amountMin, $amountMax]);
    }

    // Filter by invoice ID
    if($request->has('invoice_id')){
        $query->where('id', $request->invoice_id);
    }

    // Order by id in descending order
    $query->orderBy('id', 'DESC');

    // Get the filtered invoices
    $query->where('is_completed', 1);
    $invoices = $query->get(['id', 'name', 'total_amount', 'invoice_date', 'type']);

    // Calculate total amount and total transactions
    $totalAmount = round($invoices->sum('total_amount'),2);
    $totalTransactions = $invoices->count();

    return response([
        'status' => true,
        'total_amount' => $totalAmount,
        'total_transactions' => $totalTransactions,
        'data' => $invoices,
        'message' => "Data fetched."
    ], 200);
}

public function getExpenseReport(Request $request){
    $query = Expenses::query();

    // Filter by day
    if($request->has('day')){
        $query->whereDate('created_at', $request->day);
    }

    // Filter by month
    if($request->has('month')){
        $query->whereMonth('created_at', $request->month);
    }

    // Filter by week
    if($request->has('week_start')){
        // Assuming the week is passed as an array with start and end dates
        $query->whereBetween('created_at', [$request->week_start, $request->week_end]);
    }

    // Filter by year
    if($request->has('year')){
        $query->whereYear('created_at', $request->year);
    }

    // Filter by expense type (0 or 1)
    if($request->has('type')){
        $type = $request->type;
        $query->where('type', $type);
    }

        // Filter by name
        if($request->has('name')){
            $name = $request->name;
            $query->where('name', 'like', '%' . $name . '%');
        }

            // Filter by amount range
    if($request->has('amount_min') && $request->has('amount_max')){
        $amountMin = $request->amount_min;
        $amountMax = $request->amount_max;
        $query->whereBetween('amount', [$amountMin, $amountMax]);
    } 

    if($request->has('expense_id')){
        $query->where('id', $request->expense_id);
    }

        // Order by id in descending order
        $query->orderBy('id', 'DESC');


    $expenses = $query->get();
        // Calculate total amount and total transactions
        $totalAmount = round($expenses->sum('amount'),2);
        $totalTransactions = $expenses->count();

    return response([
        'status' => true,
        'total_expense' => $totalAmount,
        'total_transactions' => $totalTransactions,
        'url' => 'URL will be avaliable.',
        'data' => $expenses,
        'message' => "Data Feteched."
    ], 200);
}

public function getPurchaseSaleInvoice(Request $request){
    $query = Invoice::query();

    // Filter by day
    if($request->has('day')){
        $query->whereDate('invoice_date', $request->day);
    }

    // Filter by month
    if($request->has('month')){
        $query->whereMonth('invoice_date', $request->month);
    }

    // Filter by week
    if($request->has('week_start')){
        // Assuming the week is passed as an array with start and end dates
        $query->whereBetween('invoice_date', [$request->week_start, $request->week_end]);
    }

    // Filter by year
    if($request->has('year')){
        $query->whereYear('invoice_date', $request->year);
    }

    // Filter by payment mode
    if($request->has('payment_mode')){
        $paymentMode = $request->payment_mode;
        $query->where('payment_mode', $paymentMode);
    }

    // Filter by invoice type (normal only)
    $query->where('type', 'normal');

    // Filter by name
    if($request->has('name')){
        $name = $request->name;
        $query->where('name', 'like', '%' . $name . '%');
    }

    // Filter by amount range
    if($request->has('amount_min') && $request->has('amount_max')){
        $amountMin = $request->amount_min;
        $amountMax = $request->amount_max;
        $query->whereBetween('total_amount', [$amountMin, $amountMax]);
    }

    // Filter by invoice ID
    if($request->has('invoice_id')){
        $query->where('id', $request->invoice_id);
    }

    // Ensure only completed invoices are considered
    $query->where('is_completed', 1);

    // Order by id in descending order
    $query->orderBy('id', 'DESC');

    // Select only the specified columns
    $invoices = $query->get(['id', 'name', 'total_amount', 'total_igst', 'total_cgst', 'total_dgst', 'invoice_date', 'type']);

    // Calculate total GST, amount excluding GST, and aggregate totals
    $totalGST = 0;
    $totalExcludingGST = 0;
    foreach ($invoices as $invoice) {
        $invoice->total_gst = $invoice->total_igst + $invoice->total_cgst + $invoice->total_dgst;
        $invoice->amount_excluding_gst = $invoice->total_amount - $invoice->total_gst;
        $totalGST += $invoice->total_gst;
        $totalExcludingGST += $invoice->amount_excluding_gst;
    }

    // Calculate total amount and total transactions
    $totalAmount = round($invoices->sum('total_amount'), 2);
    $totalTransactions = $invoices->count();

    return response([
        'status' => true,
        'total_amount' => $totalAmount,
        'total_transactions' => $totalTransactions,
        'total_gst' => round($totalGST, 2),
        'total_excluding_gst' => round($totalExcludingGST, 2),
        'URL' => "URL, Red PDF Icon.",
        'data' => $invoices,
        'message' => "Data fetched."
    ], 200);
}
public function getInvoiceListReport(Request $request){
    $query = Invoice::query();

    // Filter by day
    if($request->has('day')){
        $query->whereDate('invoice_date', $request->day);
    }

    // Filter by month
    if($request->has('month')){
        $query->whereMonth('invoice_date', $request->month);
    }

    // Filter by week
    if($request->has('week_start')){
        // Assuming the week is passed as an array with start and end dates
        $query->whereBetween('invoice_date', [$request->week_start, $request->week_end]);
    }

    // Filter by year
    if($request->has('year')){
        $query->whereYear('invoice_date', $request->year);
    }

    // Filter by invoice type or performa
    if($request->has('type')){
        $Type = $request->type;
        $query->where('type', $Type);
    }

    // Filter by name
    if($request->has('name')){
        $name = $request->name;
        $query->where('name', 'like', '%' . $name . '%');
    }

    // Filter by amount range
    if($request->has('amount_min') && $request->has('amount_max')){
        $amountMin = $request->amount_min;
        $amountMax = $request->amount_max;
        $query->whereBetween('total_amount', [$amountMin, $amountMax]);
    }

    // Filter by invoice ID
    if($request->has('invoice_id')){
        $query->where('id', $request->invoice_id);
    }

    // Order by id in descending order
    $query->orderBy('id', 'DESC');

    // Get the filtered invoices
    $query->where('is_completed', 1);
    $invoices = $query->get(['id', 'name', 'total_amount', 'invoice_date', 'type']);

    // Calculate total amount and total transactions
    $totalAmount = round($invoices->sum('total_amount'),2);
    $totalTransactions = $invoices->count();

    return response([
        'status' => true,
        'total_amount' => $totalAmount,
        'total_transactions' => $totalTransactions,
        'url' => "Red PDF Icon",
        'data' => $invoices,
        'message' => "Data fetched."
    ], 200);
}
public function getExistedUser(Request $request)
{
    // Validate the request to ensure either 'name' or 'mobile_number' is provided
    $request->validate([
        'mobile_number' => 'required',
    ]);
    $mobileNumber = $request->input('mobile_number');

    // Query to find the user based on name or mobile number
    $query = Invoice::query();

    if ($mobileNumber) {
        $query->where('mobile_number', 'like', '%' . $mobileNumber . '%');
    }

    // Retrieve the first matching invoice
    $invoice = $query->first();

    // If no invoice found, return an error response
    if (!$invoice) {
        return response([
            'status' => false,
            'message' => 'No user found with the provided name or mobile number.'
        ], 404);
    }

    // Get billing and shipping addresses
    $billingAddress = Addres::where('invoice_id', $invoice->id)->where('type', 'billing')->first();
    $shippingAddress = Addres::where('invoice_id', $invoice->id)->where('type', 'shipping')->first();

    // Prepare response data
    $data = [
        'name' => $invoice->name,
        'mobile_number' => $invoice->mobile_number,
        'customer_type' => $invoice->customer_type,
        'doc_no' => $invoice->doc_no,
        'billing_id' => $invoice->billing_address_id,
        'shipping_id' => $invoice->shipping_address_id,
        'billing_address' => $billingAddress ? $billingAddress->only(['address_1', 'address_2', 'city', 'state', 'pincode']) : null,
        'shipping_address' => $shippingAddress ? $shippingAddress->only(['address_1', 'address_2', 'city', 'state', 'pincode']) : null,
    ];

    return response([
        'status' => true,
        'data' => $data,
        'message' => 'User data fetched successfully.'
    ], 200);
}
public function dashboardReport(Request $request)
{
    // Get Sale Report
    $saleQuery = Invoice::query();
    $saleQuery->where('is_completed', 1);
    $saleQuery->orderBy('id', 'DESC');
    $sales = $saleQuery->get(['id', 'name', 'total_amount', 'invoice_date', 'type']);
    $actualSaleAmount = round($sales->sum('total_amount'), 2);

    // Get Purchase Sale Invoice Report
    $purchaseSaleQuery = Invoice::query();
    $purchaseSaleQuery->where('is_completed', 1)->where('type', 'normal');
    
    $purchaseSales = $purchaseSaleQuery->get(['id', 'total_dgst', 'total_cgst', 'total_igst', 'total_amount']);
    $totalGst = round($purchaseSales->sum(function($invoice) {
        return $invoice->total_dgst + $invoice->total_cgst + $invoice->total_igst;
    }), 2);
    $totalExcludingGst = round($purchaseSales->sum('total_amount') - $totalGst, 2);

    // Get Invoice Report (Item Purchases)
    // Get Invoice Report (Item Purchases)
// Build the query to join items with completed and normal type invoices
$itemQuery = Item::query()
    ->join('invoices', 'items.invoice_id', '=', 'invoices.id')
    ->where('invoices.is_completed', 1)
    ->where('invoices.type', 'normal');

// Debug the query
$sqlQuery = $itemQuery->toSql();
$params = $itemQuery->getBindings();


// Fetch the items' quantities
$items = $itemQuery->get(['items.quantity']);

// Debug the fetched items


// Calculate the total quantity of items purchased
$totalItemsPurchased = $items->sum('quantity');




    $query = Expenses::query();
    $expenses = $query->get();
    // Calculate total amount and total transactions
    $totalAmount = round($expenses->sum('amount'),2);

    // Prepare response data
    $data = [
        'actual_sale_amount' => $actualSaleAmount,
        'total_excluding_gst' => $totalExcludingGst,
        'total_expense' => $totalAmount,
        'total_gst' => $totalGst,
        'total_items_purchased' => $totalItemsPurchased,
        'profit_loss' => null
    ];

    return response([
        'status' => true,
        'data' => $data,
        'message' => 'Dashboard data fetched successfully.'
    ], 200);
}



 



}
