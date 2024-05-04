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
        // Find the invoice by ID
        $invoice = Invoice::find($request->id);

        // If invoice not found, return error
        if (!$invoice) {
            return response()->json(['status' => false, 'message' => 'Invoice not found.'], 404);
        }

        // Update the invoice fields
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
        $invoice->is_completed = $request->is_completed;
        $invoice->invoice_date = $request->invoice_date;
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

        $item = new Item();
        $item->product_id = $product->id;
        $item->invoice_id = $request->invoice_id;
        $item->quantity = $request->quantity;
       
        if($request->is_gst==1){
            $item->price_of_one = $request->price;
        }else{
            $item->price_of_one = $request->price/1.18;
        }
        $address = Addres::where('invoice_id',$request->invoice_id)->first();
        // Calculate GST based on whether it's inclusive or exclusive
        if ($request->is_gst == 1) {
            // Inclusive GST
            $item->is_gst = 1;
            // Check if the address place is Delhi
            if ($address->state == 'delhi' || $address->state == 'Delhi') {
                $item->dgst = (0.09 * $item->price_of_one)*$request->quantity; // 9% GST for Delhi
                $item->cgst = (0.09 * $item->price_of_one)*$request->quantity; // 9% GST for Delhi
                $item->igst = 0; // No IGST for Delhi
            } else {
                $item->dgst = 0; // No DGST for other states
                $item->cgst = 0; // No CGST for other states
                $item->igst = (0.18 * $item->price_of_one)*$request->quantity; // 18% IGST for other states
            }
        } else {
            // Exclusive GST
            $item->is_gst = 0;
            if ($address->state == 'delhi' || $address->state == 'Delhi') {
                $item->dgst = (0.09 * $item->price_of_one)*$request->quantity; // 9% GST for Delhi
                $item->cgst = (0.09 * $item->price_of_one)*$request->quantity; // 9% GST for Delhi
                $item->igst = 0; // No IGST for Delhi
            } else {
                $item->dgst = 0; // No DGST for other states
                $item->cgst = 0; // No CGST for other states
                $item->igst = (0.18 * $item->price_of_one)*$request->quantity; // 18% IGST for other states
            }
            $item->price_of_all = $item->price_of_one*$request->quantity;
        }
        $item->price_of_all = $item->price_of_one*$request->quantity + $item->dgst + $item->cgst + $item->igst;
        $item->save();
        $update_main = Invoice::find($request->invoice_id);
        $update_main->total_dgst = $update_main->total_dgst+$item->dgst;
        $update_main->total_cgst = $update_main->total_cgst+$item->cgst;
        $update_main->total_igst = $update_main->total_igst+$item->igst;
        $update_main->total_amount = $update_main->total_amount + $item->price_of_all;
        $update_main->save();
        
        return response()->json(['status' => true, 'message' => 'Product Added successfully.', 'data' => $item], 201);
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
            $update_main->total_dgst = $update_main->total_dgst-$item->dgst;
            $update_main->total_cgst = $update_main->total_cgst-$item->cgst;
            $update_main->total_igst = $update_main->total_igst-$item->igst;
            $update_main->total_amount = $update_main->total_amount - $item->price_of_all;
            $update_main->save();
        }
        // Delete the item
        $item->delete();
        return response()->json(['status' => true, 'message' => 'Item removed successfully.'], 200);
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
            if ($request->hasFile('file')) {
                $file = $request->file('file')->store('public/expense');
                $expense->file = $file;
            }
            $expense->save();
        }else{
            $expense = new Expenses();
            $expense->name = $request->name;
            $expense->amount = $request->amount;
            $expense->type = $request->type;
            if ($request->hasFile('file')) {
                $file = $request->file('file')->store('public/expense');
                $expense->file = $file;
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
    if($request->has('expense_id')){
        $query->where('id', $request->expense_id);
    }

    $expenses = $query->get();

    return response([
        'status' => true,
        'data' => $expenses
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

 



}
