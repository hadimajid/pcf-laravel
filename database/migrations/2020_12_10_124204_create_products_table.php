<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('ProductNumber')->unique()->nullable();
            $table->string('Name');
            $table->string('slug')->unique()->nullable();
            $table->text('Description')->nullable();
            $table->unsignedBigInteger('ProductInfoId');
            $table->foreign('ProductInfoId')->references('id')->on('product_infos')->onDelete('restrict');
            $table->unsignedBigInteger('StyleId')->nullable();
            $table->foreign('StyleId')->references('id')->on('styles')->onDelete('restrict');
            $table->unsignedBigInteger('CollectionId')->nullable();
            $table->foreign('CollectionId')->references('id')->on('collection_models')->onDelete('restrict');
            $table->unsignedBigInteger('ProductLineId');
            $table->foreign('ProductLineId')->references('id')->on('product_lines')->onDelete('restrict');
            $table->string('FabricColor')->nullable();
            $table->string('FinishColor')->nullable();
            $table->float('BoxWeight')->nullable();
            $table->float('Cubes')->nullable();
            $table->unsignedBigInteger('GroupId')->nullable();
            $table->foreign('GroupId')->references('id')->on('groups')->onDelete('restrict');
//            $table->unsignedBigInteger('BoxSizeId')->nullable();
//            $table->foreign('BoxSizeId')->references('id')->on('box_sizes')->onDelete('restrict');
            $table->string('TypeOfPackaging')->nullable();
            $table->string('CatalogYear')->nullable();
            $table->string('SubBrand')->nullable();
            $table->string('Kit Type')->nullable();
            $table->string('UnitStock')->nullable();
            $table->string('Upc')->nullable();
            $table->unsignedBigInteger('CategoryId')->nullable();
            $table->foreign('CategoryId')->references('id')->on('categories')->onDelete('restrict');
            $table->unsignedBigInteger('SubcategoryId')->nullable();
            $table->foreign('SubCategoryId')->references('id')->on('sub_categories')->onDelete('restrict');
            $table->unsignedBigInteger('PieceId')->nullable();
            $table->foreign('PieceId')->references('id')->on('pieces')->onDelete('restrict');
            $table->string('CountryOfOrigin')->nullable();
            $table->string('DesignerCollection')->nullable();
            $table->boolean('AssemblyRequired')->nullable();
            $table->boolean('IsDiscontinued')->nullable();
            $table->integer('NumImages')->nullable();
            $table->integer('NumBoxes')->nullable();
            $table->integer('PackQty')->nullable();
            $table->string('CatalogPage')->nullable();
            $table->string('FabricCleaningCode')->nullable();
            $table->integer('NumHDImages')->nullable();
            $table->integer('NumNextGenImages')->nullable();
//            $table->unsignedBigInteger('InventoryId')->nullable();
            $table->boolean('Featured')->nullable();
            $table->float('BoxLength')->nullable();
            $table->float('BoxWidth')->nullable();
            $table->float('BoxHeight')->nullable();
            $table->string('RoomName')->nullable();
            $table->string('WoodFinish')->nullable();
            $table->string('ChemicalList')->nullable();
            $table->string('FeaturedImage')->nullable();
            $table->string('Promotion')->nullable();
            $table->float('SalePrice')->nullable();
            $table->boolean('Hide')->nullable()->default(0);
            $table->boolean('New')->nullable()->default(0);
            $table->boolean('Hot')->nullable()->default(0);
//            $table->foreign('InventoryId')->references('id')->on('warehouse_inventories')->onDelete('restrict');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
