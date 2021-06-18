<?php

namespace App\Imports;

use App\Models\AdditionalField;
use App\Models\Category;
use App\Models\CollectionModel;
use App\Models\Component;
use App\Models\Feature;
use App\Models\Group;
use App\Models\Material;
use App\Models\Measurement;
use App\Models\NextGenImage;
use App\Models\Piece;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductInfo;
use App\Models\RelatedProductList;
use App\Models\Style;
use App\Models\SubCategory;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\ImageManagerStatic as Image;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;


class ProductImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     * @throws \Exception
     */
    public function collection(Collection $collection)
    {
        $count = 0;
        //
//        $collection=collect($collection);
        foreach ($collection as $key=>$row) {
//            dd($row);
            $rules = $this->rules();
            $validator = Validator::make($row->all(), $rules);
            if ($validator->fails()) {
                $product = collect($validator->errors());
//                $errors;
                foreach ($product as $key2=>$error){
                    $errors=str_replace('.','',$error[0])."  (row #".($key+2).")";
                    Session::push('Errors', $errors);
//                    $errors[]=str_replace('.','',$error[0])." on row ".($key+1);
                }
//                dd($errors);

            }
            else{
                try {
                    DB::beginTransaction();

                  $category_id = Category::where('CategoryName', $row['category'])->first();
                    $subcategory_id = SubCategory::where('CategoryId', $category_id->id)->where('SubCategoryName', $row['sub_category'])->first();
                    $group = null;
                    if ($row['group_number']!=null && $row['group_name']!=null) {
                        $group = Group::create([
                            'GroupNumber' => $row['group_number'],
                            'GroupName' => $row['group_name']
                        ]);
                    }
                    $style = Style::create([
                        'StyleName' => $row['style_name']
                    ]);
                    $piece = Piece::create([
                        'PieceName' => $row['piece_name'],
                        'SubCategoryId' => $subcategory_id->id
                    ]);
                    $collection = CollectionModel::create([
                        'CollectionName' => $row['collection']
                    ]);
                    $assembly_required = $row['assembly_required'];
                    if ($assembly_required == 'true') {
                        $assembly_required = 1;
                    } else {
                        $assembly_required = 0;
                    }
                    $is_discontinued = 0;
                    $featured = 0;

                    $promotion = 0;

                    $multiColor = 0;
                    if ($row['multi_color'] == 'true') {
                        $multiColor = 1;
                    } else {
                        $multiColor = 0;
                    }
                    $pi = null;
                    if (!empty($row['features'])) {
                        $pi = ProductInfo::create([
                            'ProductName' => $row['name'],
                            'Description' => $row['description']
                        ]);
                        foreach ($row->features as $feature) {
                            Feature::create(['Name' => $feature, 'ProductInfoId' => $pi->id]);
                        }
                    }

                    $product = Product::create([
                        'Name' => $row['name'],
                        'Description' => $row['description'],
//                'FabricColor' => $request->input('fabric_color'),
//                'FinishColor' => $request->input('finish_color'),
                        'BoxWeight' => $row['box_weight'],
                        'Cubes' => $row['cubes'],
                        'TypeOfPackaging' => $row['type_of_packaging'],
                        'CatalogYear' => $row['catalog_intro'],
                        'SubBrand' => isset($row['sub_brand']),
                        'KitType' => $row['kit'],
                        'Upc' => $row['upc'],
                        'CountryOfOrigin' => $row['country_of_origin'],
                        'DesignerCollection' => isset($row['designer_collection']),
                        'AssemblyRequired' => $assembly_required,
                        'IsDiscontinued' => $is_discontinued,
                        'NumBoxes' => isset($row['num_boxes']),
                        'PackQty' => isset($row['pack_qty']),
                        'CatalogPage' => isset($row['catalog_page']),
                        'FabricCleaningCode' => $row['fabric_cleaning_code'],
                        'StyleId' => $style->id,
                        'ProductLineId' => null,
                        'GroupId' => $group ? $group->id : null,
                        'CategoryId' => $category_id->id,
                        'SubCategoryId' => $subcategory_id->id,
                        'PieceId' => $piece->id,
                        'Featured' => $featured,
                        'BoxLength' => $row['box_length'],
                        'BoxWidth' => $row['box_width'],
                        'BoxHeight' => $row['box_height'],
                        'RoomName' => $row['room_name'],
                        'WoodFinish' => $row['wood_finish'],
                        'ChemicalList' => $row['chemical_list'],
                        'PromotionCheck' => $promotion,
                        'SalePrice' => $row['price'],
                        'CollectionId' => $collection->id,
                        'ProductInfoId' => $pi ? $pi->id : null,
                        'multi_color' => $multiColor,
                        'import' => 1,
                        'hide' => 1,
                    ]);

                    Measurement::create([
                        'PieceName' => $row['item_description'],
                        'Length' => $row['length'],
                        'Width' => $row['width'],
                        'Depth' => $row['depth'],
                        'Height' => $row['height'],
                        'Diameter' => $row['diameter'],
                        'DepthOpen' => $row['depth_open'],
                        'HeightOpen' => $row['height_open'],
                        'SeatWidth' => $row['seat_width'],
                        'SeatDepth' => $row['seat_depth'],
                        'SeatHeight' => $row['seat_height'],
                        'ArmHeight' => $row['arm_height'],
                        'DeskClearance' => $row['desk_clearance'],
                        'ShelfDistance' => $row['shelf_distance'],
                        'ProductId' => $product['id']
                    ]);


                    if (!empty($row['material'])) {

                        Material::create([
                            'Field' => "Material",
                            'Value' => $row['material'],
                            'ProductId' => $product->id
                        ]);

                    }

                    $warehouse_id = Warehouse::where('Name', $row['warehouse'])->first();
                    $w = WarehouseInventory::create([
                        'WarehouseId' => $warehouse_id->id,
                        'QtyAvail' => $row['stock'],
                        'ProductId' => $product->id,
                    ]);
                    $check = false;
                    $i = 0;

                    while ($check == false) {
                        try {
                            $product->slug = Str::slug($product->Name, '-');
                            $check = $product->save();
                        } catch (\Exception $exception) {

                            $product->slug = Str::slug($product->Name, '-') . '-' . time() . uniqid();
                            $check = $product->save();
                        }
                    }
                    $colors = explode(',', $row['color']);
                    if ($multiColor) {

                        foreach ($colors as $color) {
                            $productColor = new ProductColor([
                                'name' => $color,
                                'product_id' => $product->id
                            ]);
                            $productColor->save();
                        }
                    } else {
                        $product->FabricColor = $colors[0];
                        $product->save();
                    }
                    $count++;
                    //image here
                    if (isset($row['images']))
                    {
                        $listImage = explode(',', $row['images']);
                        foreach ($listImage as $image) {
                            try {
                                if(!empty($image)){
                                    $img = Image::make($image);
                                    $image_resize = Image::make($image);
                                    $image_resize->resize(300, null, function ($constraint) {
                                        $constraint->aspectRatio();
                                    });
//                                    dd($img);
                                    $imgdata=getimagesize($image) ;

                                    $extension = explode('/', $imgdata['mime']);
//                                    dd($extension);
                                    $name = time() . uniqid()  . '.' . $extension[1];
                                    $img->save(public_path('uploads/product/' . $name));
                                    $image_resize->save(public_path('thumbnail/uploads/product/' . $name));
                                NextGenImage::create([
                                    'Name' => 'uploads/product/' . $name,
                                    'ProductId' => $product->id
                                ]);
                                }


                            } catch (\Exception $ex) {

                            }
                        }
                    }
                    DB::commit();
                }
                catch (\Exception $ex) {
                    DB::rollBack();
                    throw ($ex);
                }
            }

//            $row=collect($row);
//            dd($row['group_number']);

        }
        if ($count>0)
        {
            Session::put('count',$count.' rows inserted');
        }
        else{
            Session::put('count','');

        }

    }
    public function rules()
    {
        return [
            'name' => 'required',
            'description' => 'required',
            'fabric_color' => 'nullable',
            'finish_color' => 'nullable',
            'box_weight' => 'required|numeric',
            'cubes' => 'nullable|numeric',
            'type_of_packing' => 'nullable',
            'catalog_year' => 'nullable',
            'sub_brand' => 'nullable',
            'upc' => 'nullable',
            'country_of_origin' => 'nullable',
            'designer_collection' => 'nullable',
            'assembly_required' => 'nullable',
            'num_boxes' => 'nullable|numeric',
            'pack_qty'=>'nullable|numeric',
            'catalog_page' => 'nullable',
            'fabric_cleaning_code' => 'nullable',
            'style_name' => 'required|unique:styles,StyleName',
            'category' => 'required|exists:categories,CategoryName',
            'sub_category' => 'required|exists:sub_categories,SubCategoryName',
            'kit' => 'nullable',
            'box_length' => 'required|numeric',
            'box_width' => 'required|numeric',
            'box_height' => 'required|numeric',
            'measurements' => 'nullable|array|max:1',
            'measurements.*' => 'nullable',
            'item_description' => 'nullable',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'depth' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'diameter' => 'nullable|numeric',
            'depth_open' => 'nullable|numeric',
            'height_open' => 'nullable|numeric',
            'seat_width' => 'nullable|numeric',
            'seat_depth' => 'nullable|numeric',
            'seat_height' => 'nullable|numeric',
            'arm_height' => 'nullable|numeric',
            'desk_clearance' => 'nullable|numeric',
            'shelf_distance' => 'nullable|numeric',
            'materials' => 'nullable|array|min:1',
            'materials.*' => 'nullable',
            'materials.*.field' => 'nullable',
            'materials.*.value' => 'nullable',
            'additional_field_list' => 'nullable|array|min:1',
            'additional_field_list.*' => 'nullable',
            'additional_field_list.*.field' => 'nullable',
            'additional_field_list.*.value' => 'nullable',
            'related_product_list' => 'nullable|array|min:1',
            'related_product_list.*' => 'nullable',
            'related_product_list.*.id' => 'nullable',
            'warehouse' => 'required|exists:warehouses,Name',
            'stock' => 'required|numeric',
            'price' => 'required|numeric',
            'group_name' => 'nullable',
            'group_number' =>  'nullable|unique:groups,GroupNumber',
            'piece_name' => 'required',
            'collection' => 'required',
            'promotion'=>'nullable',
            'features'=>'nullable|array|min:1',
            'colors'=>'nullable|array|min:1'
        ];
    }
//    public function customValidationMessages()
//    {
//        return [
//            '*.*.required' => ':attribute is required',
//            '*.*.numeric' => ':attribute should be a numeric value',
//            '*.*.unique' => ':attribute should be a unique value',
//            '*.*.exists' => ':attribute should be a valid name',
//
//        ];
//    }
}
