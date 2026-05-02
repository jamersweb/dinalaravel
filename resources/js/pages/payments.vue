<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div v-if="productDiv" style="position:absolute;top:0;left:0;height:100vh;width:100vw;z-index:998;" @click="productDiv=!productDiv"></div>
    <div style="border: 1px solid #e7e7e7; border-radius: 1em;overflow:hidden;height:calc(100vh - 125px)">
        <div class="sideBar border-end float-start">
            <div class="p-3 d-flex justify-content-between align-items-center border-bottom">
                <p class="mb-0 fw-bold" style="font-size:14px;">Payments</p>
                <!-- <button class="prim_btn py-1 px-4 rounded-1" style="font-size:12px;">Live</button> -->
                <!-- <button class="prim_btn py-1 px-4 rounded-1" style="font-size:12px;">Live</button> -->
            </div>
            <div>
                <div @click="activeTab='summary',showSummary()" class="grey_bg p-3 border-bottom" :class="{active:activeTab=='summary'}"  style="cursor:pointer;">
                    <h6 class="px-3" :class="{active:activeTab=='summary'}" style="font-weight:bold;">Summary</h6>
                </div>
                <div @click="activeTab='products',showProducts()" :class="{active:activeTab=='products'}" class="grey_bg p-3 border-bottom" style="cursor:pointer;">
                    <h6 class="px-3" :class="{active:activeTab=='products'}" style="font-weight:bold;">Products</h6>
                </div>
                <div @click="activeTab='sales'" :class="{active:activeTab=='sales'}" class="grey_bg p-3 border-bottom" style="cursor:pointer;">
                    <h6 class="px-3" :class="{active:activeTab=='sales'}" style="font-weight:bold;">Sales</h6>
                </div>
                <div @click="activeTab='refund'" :class="{active:activeTab=='refund'}" class="grey_bg p-3 border-bottom" style="cursor:pointer;">
                    <h6 class="px-3" :class="{active:activeTab=='refund'}" style="font-weight:bold;">Refunds</h6>
                </div>
                <!-- <div @click="showTransactions" :class="{active:transactions}" class="grey_bg p-3 border-bottom" style="cursor:pointer;">
                    <h6 class="px-3" :class="{active:transactions}" style="font-weight:bold;">Transcations</h6>
                </div> -->
                <!-- <div @click="showDisputes" :class="{active:disputes}" class="grey_bg p-3 border-bottom" style="cursor:pointer;">
                    <h6 class="px-3" :class="{active:disputes}" style="font-weight:bold;">Disputes</h6>
                </div> -->
                <div @click="activeTab='discountcode',showDiscountCode()" :class="{active:activeTab=='discountcode'}" class="grey_bg p-3 border-bottom" style="cursor:pointer;">
                    <h6 class="px-3" :class="{active:activeTab=='discountcode'}" style="font-weight:bold;">Discount Code</h6>
                </div>
                <!-- <div @click="showSetup" :class="{active:setup}" class="grey_bg p-3 border-bottom" style="cursor:pointer;">
                    <h6 class="px-3" :class="{active:setup}" style="font-weight:bold;">Setup</h6>
                </div> -->
            </div>
        </div>
        <div v-if="activeTab=='summary'" class="position-relative float-start" style="width:calc(100% - 250px);height:100%;">
            <div class="topBar d-flex justify-content-between">
                <div class="px-4 py-2">
                    <h5 class="fw-bold">Last {{paymentBtn}}</h5>
                    <p v-if="paymentsSummaryData!=null" class="mb-0" style="font-size:12px;">{{paymentsSummaryData.start_date}} - {{paymentsSummaryData.end_date}}</p>
                </div>
                <div class="p-3">
                    <button :class="{topbar_btn_active:paymentBtn==='month'}" @click="changePaymentTime(0)" class="topbar_btn fw-bold px-4 mx-2 rounded-pill" style="font-size:12px;">1M</button>
                    <button :class="{topbar_btn_active:paymentBtn==='3months'}" @click="changePaymentTime(1)" class="topbar_btn fw-bold px-4 mx-2 rounded-pill" style="font-size:12px;">3M</button>
                    <button :class="{topbar_btn_active:paymentBtn==='6months'}" @click="changePaymentTime(2)" class="topbar_btn fw-bold px-4 mx-2 rounded-pill" style="font-size:12px;">6M</button>
                    <button :class="{topbar_btn_active:paymentBtn==='year'}" @click="changePaymentTime(3)" class="topbar_btn fw-bold px-4 mx-2 rounded-pill" style="font-size:12px;">1Y</button>
                </div>
            </div>
            <div class="pb-5" style="width:100%;height:calc(100vh - 210px);overflow:auto;">
                <graph v-if="paymentsSummaryData!=null" :name="'Gross Revenue'" :dataset="paymentsSummaryData.gross_dataset" :label="paymentsSummaryData.labels" :revenue="paymentsSummaryData.gross_revenue" :item="'Subscription'"/>
                <graph v-if="paymentsSummaryData!=null" :name="'Net Revenue'" :dataset="paymentsSummaryData.net_dataset" :label="paymentsSummaryData.labels" :revenue="paymentsSummaryData.net_revenue" :item="'Subscription'"/>
                <graph v-if="paymentsSummaryData!=null" :name="'Products Sold'" :dataset="paymentsSummaryData.sales_dataset" :label="paymentsSummaryData.labels" :revenue="paymentsSummaryData.total_sales" :item="'Subscription'"/>
                <graph v-if="paymentsSummaryData!=null" :name="'Active Subscribers'" :dataset="paymentsSummaryData.active_dataset" :label="paymentsSummaryData.labels" :revenue="paymentsSummaryData.total_active" :item="'Clients'"/>
            </div>
        </div>
        <div v-if="activeTab=='products'" class="position-relative float-start" style="width:calc(100% - 250px);height:100%;overflow:auto;">
            <div class="d-flex justify-content-between ps-3 pt-2" style="width:100%;min-height:50px;float:right;background-color: #eeeeee;">
                <div>
                    <p class="m-0" style="font-size:26px;">Products</p>
                </div>
                <div>
                    <div class="position-relative float-start me-2" style="width:235px;height:35px">
                        <input type="text" placeholder="search for products" class="py-2 pe-2 ps-4 w-100" v-model="search" style="background-color: white;border: 1px solid rgb(167, 166, 166);border-radius: 10px;font-size: 10px;">
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute" style="max-height: 12px;left: 7px;top: 10px;">
                    </div>
                    <!-- <div class="filter-btn ms-2 float-start" >
                        <button style="border:none;background-color:transparent">
                            <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid" style="max-height: 20px;left: 7px;top: 10px;">
                        </button>
                    </div> -->
                </div>
            </div>
            <!-- <div class="d-flex justify-content-between px-3 pt-3" style="width:100%;min-height:60px;">
                <div>
                    <button class="prim_btn py-1 px-1 rounded-1 float-start ms-2" style="font-size:13px;" @click="addProductPopup">Add Product</button>
                    <button class="prim_btn py-1 px-3 rounded-1 float-start ms-2" style="font-size:13px;">Export</button>
                    <div class="dropdown float-start ms-2 " style="margin-top:1px;">
                        <button class="three-dots2 tsl position-relative" data-bs-toggle="dropdown">
                            <img src="/cms-assets/images/master-libraries/three-dots.png" style="width:15%" alt="">
                        </button>
                        <ul class="dropdown-menu tsl">
                            <li><button class="dropdown-item">Rename</button></li>
                            <li><button class="dropdown-item">Delete</button></li>
                        </ul>
                    </div>
                </div>
                <div>
                    <select class="brds-1" style="height:28px;width:170px;border-color:#EDEDED;font-size:12px;">
                        <option>Name</option>
                    </select>
                </div>
            </div> -->
            <div class="p-3" style="width:100%;height:calc(100vh - 180px);overflow:auto;">
                <div class="row col-12 mx-0">
                    <div class="col-xl-4 col-md-6 col-sm-12 col-12 p-0"  v-for="(item, index) in filteredProducts" :key="index">
                        <div v-if="allProducts!==null" class="tsl brds-2 ms-2 pb-3 mt-3 px-3 position-relative" style="min-height:350px;">
                            <!-- <input type="checkbox" class="form-check-input" style="position:absolute;left:10px;top:5px;"> -->
                            <div style="width:100%;height:25px;">

                            </div>
                            <img :src="item.image" alt="Error" class="mb-2 img-fluid mx-auto" style="height:130px;display:block;">
                            <p class="mb-1" style="font-size:13px;font-weight:bold;">{{item.name}}</p>
                            <p class="mb-1" style="font-size:18px bold; color:#F2A18C;">$ {{item.price}} USD</p>
                            <div style="font-size:10px;color:#707070;width:100%;height:15px;">
                                <p class="mb-0" style="float:left;">Billing Cycle:</p>
                                <p class="mb-0 ms-2" style="float:left;">Monthly</p>
                            </div>
                            <div style="font-size:10px;color:#707070;width:100%;height:15px;">
                                <p class="mb-0" style="float:left;">Duration:</p>
                                <p class="mb-0 ms-2" style="float:left;">Forever</p>
                            </div>
                            <div style="font-size:10px;color:#707070;width:100%;height:15px;">
                                <p class="mb-0" style="float:left;">Client Setting:</p>
                                <p class="mb-0 ms-2" style="float:left;">Edit</p>
                            </div>
                            <div style="font-size:10px;color:#707070;width:100%;height:15px;">
                                <p class="mb-0" style="float:left;">Current:</p>
                                <p class="mb-0 ms-2" style="float:left;">{{item.clients}} Clients</p>
                            </div>
                            <div style="font-size:10px;color:#707070;width:100%;height:15px;margin-bottom:10px;">
                                <p class="mb-0" style="float:left;">Upcoming:</p>
                                <p class="mb-0 ms-2" style="float:left;">0 Clients</p>
                            </div>
                            <!-- <button @click="deleteProduct(item.id)" class="prim_bg brds-1 mt-1" style="font-size:13px;border:none;width:90px;height:25px;">Delete</button> -->
                            <button @click="editProduct(item)" class="prim_bg ms-2 brds-1 mt-1" style="font-size:13px;border:none;width:90px;height:25px;">Edit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="filters" class="my-popup-component" style="justify-content:end">
            <div class="position-relative" style="width:330px;height:100vh;background-color:white;border-top-left-radius:20px;border-bottom-left-radius:20px;">
                <button class="trans_btn position-absolute" style="right:15px;top:10px;font-size:25px" @click="filters=false">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <p class="ms-4 mt-4" style="font-size:15px;">Products Filters</p>
                <div class="w-100 d-flex justify-content-center flex-wrap">
                    <select class="brds-1 mt-2" style="height:40px;width:260px;border-color:#B1B0B0;font-size:16px;color:#F2A18C;text-align:center;">
                        <option>Date Added</option>
                    </select>
                    <select class="brds-1 mt-2" style="height:40px;width:260px;border-color:#B1B0B0;font-size:16px;color:#F2A18C;text-align:center;">
                        <option>Payment Type</option>
                    </select>
                    <select class="brds-1 mt-2" style="height:40px;width:260px;border-color:#B1B0B0;font-size:16px;color:#F2A18C;text-align:center;">
                        <option>Product Type</option>
                    </select>
                </div>
                <div class="position-absolute bottom-0 w-100 mb-3 d-flex flex-wrap justify-content-center">
                    <button class="brds-2 mb-2" style="width:180px;height:40px;border:none;background-color:#F2A18C;font-size:16px;color:#FFFFFF;">Apply</button>
                    <button class="brds-2" style="width:180px;height:40px;border:none;background-color:#F2A18C;font-size:16px;color:#FFFFFF;">Reset</button>
                </div>
            </div>
        </div>
        <div v-if="activeTab=='sales'" class="position-relative float-start" style="width:calc(100% - 250px);height:100%;overflow-y:auto;overflow-x:hidden">
            <div class="d-flex justify-content-between ps-3 pt-2" style="width:100%;min-height:50px;float:right;background-color: #eeeeee;">
                <div>
                    <p class="m-0" style="font-size:26px;">Sales</p>
                </div>
                <div>
                    <div class="position-relative float-start me-2" style="width:235px;height:35px">
                            <input type="text" placeholder="search for sales" class="py-2 pe-2 ps-4 w-100" v-model="searchForSales" style="background-color: white;border: 1px solid rgb(167, 166, 166);border-radius: 10px;font-size: 10px;">
                            <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute" style="max-height: 12px;left: 7px;top: 10px;">
                    </div>
                    <!-- <div class="filter-btn ms-2 float-start">
                        <button style="border:none;background-color:transparent">
                            <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid" style="max-height: 20px;left: 7px;top: 10px;">
                        </button>
                    </div> -->
                </div>
            </div>
            <div class="d-flex justify-content-between px-3 pt-3">
                <div>
                    <!-- <button class="prim_btn py-1 px-1 rounded-1 float-start ms-2" style="font-size:13px;" @click="addSalePopup">Add Sale</button>
                    <button class="prim_btn py-1 px-3 rounded-1 float-start ms-2" style="font-size:13px;">Export</button>
                    <div class="dropdown float-start ms-2 " style="margin-top:1px;">
                        <button class="three-dots2 tsl" data-bs-toggle="dropdown"></button>
                        <ul class="dropdown-menu tsl">
                            <li><button class="dropdown-item">Rename</button></li>
                            <li><button class="dropdown-item">Delete</button></li>
                        </ul>
                    </div> -->
                </div>
                <div>
                    <!-- <select class="brds-1" style="height:28px;width:170px;border-color:#EDEDED;font-size:12px;">
                        <option>Name</option>
                    </select> -->
                </div>
            </div>
            <div>
                <sales :searchValue="searchForSales"/>
            </div>
        </div>
        <div v-if="invoices" class="position-relative float-start" style="width:calc(100% - 250px);height:100%;overflow-y:auto;overflow-x:hidden">
            <div class="d-flex justify-content-between ps-3 pt-2" style="width:100%;min-height:50px;float:right;background-color: #eeeeee;">
                <div>
                    <p class="m-0" style="font-size:26px;">Invoices</p>
                </div>
                <div>
                    <div class="position-relative float-start me-2" style="width:235px;height:35px">
                        <input type="text" placeholder="search for invoices" class="py-2 pe-2 ps-4 w-100" v-model="search" style="background-color: white;border: 1px solid rgb(167, 166, 166);border-radius: 10px;font-size: 10px;">
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute" style="max-height: 12px;left: 7px;top: 10px;">
                    </div>
                    <!-- <div class="filter-btn ms-2 float-start">
                        <button style="border:none;background-color:transparent">
                            <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid" style="max-height: 20px;left: 7px;top: 10px;">
                        </button>
                    </div> -->
                </div>
            </div>
            <div class="d-flex justify-content-between px-3 pt-3" style="width:100%;min-height:60px;">
                <div>
                    <!-- <button class="prim_btn py-1 px-1 rounded-1 float-start ms-2" style="font-size:13px;" @click="addSalePopup">Add Sale</button> -->
                    <button class="tsl py-1 px-3 rounded-1 float-start ms-2" style="font-size:13px;border:none;width:85px;height:27px;background-color:white;">Export</button>
                    <div class="dropdown float-start ms-2 " style="margin-top:1px;">
                        <!-- <button class="three-dots2 tsl" data-bs-toggle="dropdown"></button>
                        <ul class="dropdown-menu tsl">
                            <li><button class="dropdown-item">Rename</button></li>
                            <li><button class="dropdown-item">Delete</button></li>
                        </ul> -->
                    </div>
                </div>
                <div>
                    <select class="brds-1" style="height:28px;width:170px;border-color:#EDEDED;font-size:12px;">
                        <option>Name</option>
                    </select>
                </div>
            </div>
            <div>
                <!-- <DataTable
                class="display" width="100%" :data="[]">
                    <thead>
                        <tr>
                            <th>Invoice No.</th>
                            <th>Billed On</th>
                            <th>Client</th>
                            <th>Product Name</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                </DataTable> -->
            </div>
        </div>
        <div v-if="transactions" class="position-relative float-start" style="width:calc(100% - 250px);height:100%;overflow-y:auto;overflow-x:hidden">
            <div class="d-flex justify-content-between ps-3 pt-2" style="width:100%;min-height:50px;float:right;background-color: #eeeeee;">
                <div>
                    <p class="m-0" style="font-size:26px;">Transactions</p>
                </div>
                <div>
                    <div class="position-relative float-start me-2" style="width:235px;height:35px">
                        <input type="text" placeholder="search for transactions" class="py-2 pe-2 ps-4 w-100" v-model="search" style="background-color: white;border: 1px solid rgb(167, 166, 166);border-radius: 10px;font-size: 10px;">
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute" style="max-height: 12px;left: 7px;top: 10px;">
                    </div>
                    <!-- <div class="filter-btn ms-2 float-start">
                        <button style="border:none;background-color:transparent">
                            <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid" style="max-height: 20px;left: 7px;top: 10px;">
                        </button>
                    </div> -->
                </div>
            </div>
            <div class="d-flex justify-content-between px-3 pt-3" style="width:100%;min-height:60px;">
                <div>
                    <!-- <button class="prim_btn py-1 px-1 rounded-1 float-start ms-2" style="font-size:13px;" @click="addSalePopup">Add Sale</button> -->
                    <button class="tsl py-1 px-3 rounded-1 float-start ms-2" style="font-size:13px;border:none;width:85px;height:27px;background-color:white;">Export</button>
                    <div class="dropdown float-start ms-2 " style="margin-top:1px;">
                        <!-- <button class="three-dots2 tsl" data-bs-toggle="dropdown"></button>
                        <ul class="dropdown-menu tsl">
                            <li><button class="dropdown-item">Rename</button></li>
                            <li><button class="dropdown-item">Delete</button></li>
                        </ul> -->
                    </div>
                </div>
                <div>
                    <select class="brds-1" style="height:28px;width:170px;border-color:#EDEDED;font-size:12px;">
                        <option>Name</option>
                    </select>
                </div>
            </div>
            <div>
                <!-- <DataTable
                class="display" width="100%" :data="[]">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Grass Amount</th>
                            <th>Transaction Fee</th>
                            <th>Product Name</th>
                            <th>Net Amount</th>
                            <th>Transaction Date</th>
                        </tr>
                    </thead>
                </DataTable> -->
            </div>
        </div>
        <div v-if="disputes" class="position-relative float-start" style="width:calc(100% - 250px);height:100%;overflow-y:auto;overflow-x:hidden">
            <div class="d-flex justify-content-between ps-3 pt-2" style="width:100%;min-height:50px;float:right;background-color: #eeeeee;">
                <div>
                    <p class="m-0" style="font-size:26px;">Disputes</p>
                </div>
                <div>
                    <div class="position-relative float-start me-2" style="width:235px;height:35px">
                        <input type="text" placeholder="search for invoices" class="py-2 pe-2 ps-4 w-100" v-model="search" style="background-color: white;border: 1px solid rgb(167, 166, 166);border-radius: 10px;font-size: 10px;">
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute" style="max-height: 12px;left: 7px;top: 10px;">
                    </div>
                    <!-- <div class="filter-btn ms-2 float-start">
                        <button style="border:none;background-color:transparent">
                            <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid" style="max-height: 20px;left: 7px;top: 10px;">
                        </button>
                    </div> -->
                </div>
            </div>
            <div class="d-flex justify-content-between px-3 pt-3" style="width:100%;min-height:60px;">
                <div>
                    <!-- <button class="prim_btn py-1 px-1 rounded-1 float-start ms-2" style="font-size:13px;" @click="addSalePopup">Add Sale</button> -->
                    <button class="tsl py-1 px-3 rounded-1 float-start ms-2" style="font-size:13px;border:none;width:85px;height:27px;background-color:white;">Export</button>
                    <div class="dropdown float-start ms-2 " style="margin-top:1px;">
                        <!-- <button class="three-dots2 tsl" data-bs-toggle="dropdown"></button>
                        <ul class="dropdown-menu tsl">
                            <li><button class="dropdown-item">Rename</button></li>
                            <li><button class="dropdown-item">Delete</button></li>
                        </ul> -->
                    </div>
                </div>
                <div>
                    <select class="brds-1" style="height:28px;width:170px;border-color:#EDEDED;font-size:12px;">
                        <option>Name</option>
                    </select>
                </div>
            </div>
            <div>
                <!-- <DataTable
                class="display" width="100%" :data="[]">
                    <thead>
                        <tr>
                            <th>Dispute On</th>
                            <th>Respond By</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                </DataTable> -->
            </div>
        </div>
        <div v-if="activeTab=='discountcode'" class="position-relative float-start" style="width:calc(100% - 250px);height:100%;overflow-y:auto;overflow-x:hidden">
            <div v-if="newDiscountCode" class="my-popup-component">
                <div class="brds-4 col-11 col-sm-9 col-md-7 col-xl-6 px-4 pb-3 position-relative" style="background-color:white;height:75vh;overflow-y:auto;padding-top:40px;">
                    <div v-if="productDiv" @click="productDiv=false" style="height:100%;width:100%;top:0;left:0;position:absolute;z-index:99;"></div>
                    <button class="trans_btn position-absolute" @click="quitNewDiscountCode()"
                        style="right:15px;top:5px;font-size:25px">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <p class="fw-bold col-12" style="text-align:center;font-size:23px;">Create a New Discount Code</p>
                    <div class="col-11 mx-auto my-3" style="min-height:30px;">
                        <p class="mb-0 float-start" style="width:120px">Discount Code</p>
                        <input class="float-start ms-4 px-3 py-1 brds-2 tsl border-0" v-model="postData.code" style="color:#808080;width:calc(100% - 200px)" type="text" placeholder="Enter unique discount code" name="" id="">
                    </div>
                    <div class="col-11 mx-auto my-3" style="min-height:30px;">
                        <p class="mb-0 float-start" style="width:120px;">Discount type</p>
                        <select class="float-start px-3 py-1 ms-4 brds-2 tsl border-0" v-model="postData.type" style="color:#808080;" name="" id="">
                            <option value="percentage" selected>By Percentage</option>
                            <option value="amount">By Amount</option>
                        </select>
                    </div>
                    <div class="col-11 mx-auto my-3" style="min-height:30px;">
                        <p class="mb-0 float-start" style="width:120px">Discount</p>
                        <input v-model="postData.discount" class="float-start ms-4 px-3 py-1 brds-2 tsl border-0" style="color:#808080;width:calc(100% - 200px)" type="number" min="1" placeholder="Enter discount" name="" id="">
                    </div>
                    <div class="col-11 mx-auto my-3" style="min-height:30px;">
                        <p class="mb-0 float-start" style="width:120px">Valid On</p>
                        <button @click.self="productDiv=!productDiv" class="ps-3 pe-4 py-1 tsl brds-2 border-0 ms-4 position-relative" style="background-color:transparent;color:#808080;min-width:200px">
                            <p @click="productDiv=!productDiv" v-if="selectedProducts.length<1" class="mb-0">Select a product</p>
                            <p @click="productDiv=!productDiv" class="mb-0">
                                <span v-for="(item, index) in selectedProducts" :key="index"> {{item.name}}{{(index+1)===selectedProducts.length?'':', '}}</span>
                            </p>
                            <img src="/cms-assets/images/Clients/downarrow.png" class="position-absolute" style="right:3px;height:6px;top:36%;" alt="">
                            <div v-if="productDiv" class="tsl mb-4 w-100 brds-1" style="overflow-y:auto;position:absolute;z-index:99;background-color:white;left:0;">
                                <div class="text-start hvrPro px-3 mb-1" v-for="(item, index) in allProducts" :key="index">
                                    <input type="checkbox" class="form-check-input me-4 pointer" :value="item" :id="'ck'+index" v-model="selectedProducts">
                                    <label :for="'ck'+index" class="pointer">{{item.name}}</label>
                                </div>
                            </div>
                        </button>
                    </div>
                    <div class="col-11 mx-auto my-3" style="min-height:30px;">
                        <p class="mb-0 float-start" style="width:120px;">Available For</p>
                        <select class="float-start px-3 py-1 ms-4 brds-2 tsl border-0" v-model="postData.availability" style="color:#808080;" name="" id="">
                            <option value="everyone" selected>Everyone</option>
                            <option value="specific">User Specific</option>
                        </select>
                    </div>
                    <div class="col-11 mx-auto my-3" style="min-height:30px;">
                        <p class="mb-0 float-start" style="width:120px;">Valid Till</p>
                        <div class="brds-2 tsl ms-4" style="width:calc(100% - 250px);float:left;height:35px;border:none;">
                                <Datepicker v-model="postData.valid_till" :startDate="minDate" :minDate="minDate"  autoApply ignoreTimeValidation :enableTimePicker="false"/>
                            </div>
                    </div>
                    <div v-if="postData.availability!=='everyone'" class="col-11 mx-auto my-3" style="min-height:30px;">
                        <p class="mb-0 float-start" style="width:120px">Valid For</p>
                        <input class="float-start ms-4 px-3 py-1 brds-2 tsl border-0" style="color:#808080;width:calc(100% - 250px)" type="text" placeholder="Enter clients Email for this code" v-model="userEmail">
                        <button @click="addUser" class="float-start ms-3 prim_bg border-0 brds-4">+</button>
                    </div>
                    <div v-if="postData.user_emails.length>0" class="col-11 mx-auto my-2" style="height:70px;overflow-y:auto;">
                        <div style="float:right;width:calc(100% - 145px)">
                            <p v-for="(item, index) in postData.user_emails" :key="index" class="prim_bg brds-2 px-2 py-1 mx-2 float-start" style="font-size:13px;color:white;">{{item}}</p>
                        </div>
                    </div>
                    <div class="col-11 mx-auto" style="text-align:center;">
                        <button @click="saveDiscountCode" class="prim_bg px-4 py-1 brds-2 border-0">Save</button>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between ps-3 pt-2" style="width:100%;min-height:50px;float:right;background-color: #eeeeee;">
                <div>
                    <p class="m-0" style="font-size:26px;">Discount Code</p>
                    <!-- <button @click="discountCodes=true,redemptionHistory=false" style="border:none;font-size:13px;background-color:white;width:120px;height:35px;border-top-left-radius:10px;border-bottom-left-radius:10px;" :class="{active1:discountCodes}">Discount Codes</button>
                    <button @click="redemptionHistory=true,discountCodes=false" style="border:none;font-size:13px;background-color:white;width:140px;height:35px;border-top-right-radius:10px;border-bottom-right-radius:10px;" :class="{active1:redemptionHistory}">Redemption History</button> -->
                </div>
                <div>
                    <div class="position-relative float-start me-2" style="width:235px;height:35px">
                        <input type="text" placeholder="search for Discount Code" class="py-2 pe-2 ps-4 w-100" v-model="search" style="background-color: white;border: 1px solid rgb(167, 166, 166);border-radius: 10px;font-size: 10px;">
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute" style="max-height: 12px;left: 7px;top: 10px;">
                    </div>
                    <!-- <div class="filter-btn ms-2 float-start">
                        <button style="border:none;background-color:transparent">
                            <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid" style="max-height: 20px;left: 7px;top: 10px;">
                        </button>
                    </div> -->
                </div>
            </div>
            <div class="d-flex justify-content-between px-3 pt-3" style="width:100%;min-height:60px;">
                <div>
                    <button @click="newDiscountCode=true" class="prim_btn py-1 px-1 rounded-1 float-start ms-2" style="font-size:13px;width:84px;">New</button>
                    <!-- <button class="tsl py-1 px-3 rounded-1 float-start ms-2" style="font-size:13px;border:none;width:85px;height:27px;background-color:white;">Export</button> -->
                    <!-- <div v-if="discountCodes" class="dropdown float-start ms-2 " style="margin-top:1px;">
                        <button class="three-dots2 tsl" data-bs-toggle="dropdown"></button>
                        <ul class="dropdown-menu tsl">
                            <li><button class="dropdown-item">Rename</button></li>
                            <li><button class="dropdown-item">Delete</button></li>
                        </ul>
                    </div> -->
                </div>
                <div>
                    <select class="brds-1" style="height:28px;width:170px;border-color:#EDEDED;font-size:12px;">
                        <option>Name</option>
                    </select>
                </div>
            </div>
            <div style="height:calc(100% - 110px);overflow-y:auto;">
                <Vue3EasyDataTable
                :headers="headers"
                :items="items"
                :search-field="searchField"
                :search-value="search">
                >
                <template #item-status="item">
                    <p title="Click to change the status" @click="changeStatus(item.id)" v-if="item.status==1" class="mb-0 ps-3 py-1 brds-2" style="background-color:green;width:70px;color:white;cursor:pointer;">Active</p>
                    <p title="Click to change the status" @click="changeStatus(item.id)" v-else class="mb-0 ps-2 py-1 brds-2" style="background-color:red;width:70px;color:white;cursor:pointer;">Disabled</p>
                </template>
                </Vue3EasyDataTable>
            </div>
        </div>
        <div v-if="activeTab=='refund'" class="position-relative float-start" style="width:calc(100% - 250px);height:100%;overflow-y:auto;overflow-x:hidden">
            <div class="d-flex justify-content-between ps-3 pt-2" style="width:100%;min-height:50px;float:right;background-color: #eeeeee;">
                <div>
                    <p class="m-0" style="font-size:26px;">Refunds</p>
                </div>
                <div>
                    <div class="position-relative float-start me-2" style="width:235px;height:35px">
                            <input type="text" placeholder="search for refund" class="py-2 pe-2 ps-4 w-100" v-model="searchForRefund" style="background-color: white;border: 1px solid rgb(167, 166, 166);border-radius: 10px;font-size: 10px;">
                            <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute" style="max-height: 12px;left: 7px;top: 10px;">
                    </div>
                    <!-- <div class="filter-btn ms-2 float-start">
                        <button style="border:none;background-color:transparent">
                            <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid" style="max-height: 20px;left: 7px;top: 10px;">
                        </button>
                    </div> -->
                </div>
            </div>
            <div class="d-flex justify-content-between px-3 pt-3">
                <div>
                    <!-- <button class="prim_btn py-1 px-1 rounded-1 float-start ms-2" style="font-size:13px;" @click="addSalePopup">Add Sale</button>
                        <button class="prim_btn py-1 px-3 rounded-1 float-start ms-2" style="font-size:13px;">Export</button>
                        <div class="dropdown float-start ms-2 " style="margin-top:1px;">
                            <button class="three-dots2 tsl" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu tsl">
                                <li><button class="dropdown-item">Rename</button></li>
                                <li><button class="dropdown-item">Delete</button></li>
                            </ul>
                        </div> -->
                    </div>
                    <div>
                        <!-- <select class="brds-1" style="height:28px;width:170px;border-color:#EDEDED;font-size:12px;">
                            <option>Name</option>
                        </select> -->
                    </div>
                </div>
                <div>
                <Refund :searchValue="searchForRefund" />
            </div>
        </div>
        <addProduct v-if="addProduct" :preData="productDetails"/>
    </div>
    <div v-if="productDiv" style="position:absolute;top:0;left:0;height:100vh;width:100vw;" @click="productDiv=false"></div>
</template>
<script>
import axios from 'axios';
import config from '../config';
import graph from '../components/payments/graph.vue';
import addProduct from '../components/payments/addProduct.vue';
import Vue3EasyDataTable from 'vue3-easy-data-table';
import 'vue3-easy-data-table/dist/style.css';
import Loader from '../components/loader.vue';
import Inform from '../components/inform.vue';
import sales from '../components/payments/sales.vue';
import { ref } from "vue";
import Refund from '../components/payments/refund.vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    components: { graph, addProduct, Vue3EasyDataTable, Loader, Inform, sales, Refund, Datepicker },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            searchField: ref("code"),
            summary: true,
            products: false,
            addProduct: false,
            sales: false,
            filters: false,
            invoices: false,
            transactions: false,
            disputes: false,
            discountCode: false,
            discountCodes: false,
            refund: false,
            redemptionHistory: false,
            setup: false,
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            search: '',
            newDiscountCode: false,
            itemsSelected: [],
            productDetails: null,
            paymentsSummaryData: null,
            paymentBtn: 'month',
            allProducts: [],
            searchForSales: '',
            searchForRefund: '',
            productDiv: false,
            userEmail: null,
            activeTab: 'summary',
            allDiscountCodes: null,
            selectedProducts: [],
            minDate: null,
            postData: {
                code: null,
                type: 'percentage',
                discount: null,
                user_emails: [],
                products: [],
                availability: 'everyone',
                valid_till: null
            },
            headers: [
                { text: "Discount Code", value: "code", sortable: true },
                { text: "Discount", value: "off_by", sortable: true },
                { text: "Availability", value: "availability", sortable: true },
                { text: "Valid Till", value: "valid_till", sortable: true },
                { text: "Status", value: "status", sortable: true },
                { text: "Type", value: "type", sortable: true },
            ],
            items: []
        }
    },
    mounted() {
        this.$emit('adminCheckEvent');
        this.paymentsSummary();
        let today = new Date();
        this.minDate = today;
    },
    computed: {
        filteredProducts() {
            return this.allProducts.filter((items) => {
                return items.name.toLowerCase().includes(this.search.toLowerCase());
            });
        },
    },
    methods: {
        changeStatus(m) {
            let discountCodeId = m;
            this.pageLoading = true;
            this.loaderText = 'Changing status';
            axios.get(config.baseApiUrl + 'disable-discount-code/' + discountCodeId, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done';
                    this.modalDetail = 'Status changed';
                    this.informModal = true;
                    this.getAllDiscountCodes();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        },
        quitNewDiscountCode() {
            this.newDiscountCode = false;
            this.postData.availability = 'everyone';
            this.postData.valid_till = null;
            this.postData.code = null;
            this.postData.discount = null;
            this.postData.products = [];
            this.postData.type = 'percentage';
            this.postData.user_emails = [];
            this.selectedProducts = [];
        },
        saveDiscountCode() {
            let error = false;
            if (this.postData.availability !== 'everyone') {
                if (this.userEmail !== null && this.userEmail !== '') {
                    var validRegex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    if (!this.userEmail.match(validRegex)) {
                        this.modalTitle = 'Error!';
                        this.modalDetail = 'Not a valid email';
                        this.informModal = true;
                        error = true;
                        return
                    }
                    if (this.userEmail == null) {
                        return
                    }
                    else {
                        this.postData.user_emails.push(this.userEmail);
                        this.userEmail = null;
                    }
                }
            }
            for (let index = 0; index < this.selectedProducts.length; index++) {
                this.postData.products.push(this.selectedProducts[index].id)
            }
            if(this.postData.code == null){
                this.modalTitle = 'Error!';
                this.modalDetail = 'All fields are required, please fill them up';
                this.informModal = true;
                error = true;
                return;
            } else {
                this.postData.code = this.postData.code.trim();
                if(this.postData.code==""){
                    this.modalTitle = 'Error!';
                    this.modalDetail = 'All fields are required, please fill them up';
                    this.informModal = true;
                    error = true;
                    return;
                }
            }
            if (this.postData.discount == null || this.postData.products.length < 1 || this.postData.valid_till == null) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'All fields are required, please fill them up';
                this.informModal = true;
                error = true;
                return;
            }
            if (this.postData.discount > 1000) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Value cannot be greater than 1000';
                this.informModal = true;
                error = true;
                return;
            }
            if (this.postData.availability !== 'everyone') {
                if (this.postData.user_emails.length < 1) {
                    this.modalTitle = 'Error!';
                    this.modalDetail = 'All fields are required, please fill them up';
                    this.informModal = true;
                    error = true;
                    return;
                }
            }
            if (!error) {
                this.pageLoading = true;
                this.loaderText = 'Uploading';
                axios.post(config.baseApiUrl + 'create-discount-code', this.postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done';
                        this.modalDetail = 'Discount Code created successfully';
                        this.informModal = true;
                        this.newDiscountCode = false;
                        this.postData.availability = 'everyone';
                        this.postData.valid_till = null;
                        this.postData.code = null;
                        this.postData.discount = null;
                        this.postData.products = [];
                        this.postData.type = 'percentage';
                        this.postData.user_emails = [];
                        this.selectedProducts = [];
                        this.getAllDiscountCodes();
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = er.message;
                    this.informModal = true;
                })
            }
        },
        addUser() {
            var validRegex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            if (this.userEmail == null || this.userEmail == '') {
                this.modalTitle = 'Error';
                this.modalDetail = 'Enter the Email First';
                this.informModal = true;
                return
            }
            if (!this.userEmail.match(validRegex)) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Not a valid email';
                this.informModal = true;
                return
            }
            if (this.userEmail == null) {
                return
            }
            else {
                this.postData.user_emails.push(this.userEmail);
                this.userEmail = null;
            }
        },
        deleteProduct(m) {
            let idToDelete = m;
            this.pageLoading = true;
            this.loaderText = 'Deleting';
            axios.get(config.baseApiUrl + 'delete-product/' + idToDelete, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.allProducts = null;
                    this.showProducts();
                    this.modalTitle = 'Done';
                    this.modalDetail = 'Product Deleted Successfully';
                    this.informModal = true;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        },
        editProduct(m) {
            this.productDetails = m;
            this.addProduct = true;
        },
        changePaymentTime(m) {
            if (m == 0) {
                this.paymentBtn = 'month';
            }
            if (m == 1) {
                this.paymentBtn = '3months'
            }
            if (m == 2) {
                this.paymentBtn = '6months'
            }
            if (m == 3) {
                this.paymentBtn = 'year'
            }
            this.paymentsSummary();
        },
        paymentsSummary() {
            this.paymentsSummaryData = null
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'payments-summary?duration=' + this.paymentBtn, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.paymentsSummaryData = res.data;
                    this.paymentsSummaryData.labels = this.paymentsSummaryData.labels.reverse();
                }
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            })
        },
        showSummary() {
            this.paymentsSummary();
        },
        showProducts() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-all-products', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.allProducts = res.data.data;
                }
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error';
                this.modalDetail = er;
                this.informModal = true;
            })
        },
        showSales() {
            this.sales = true;
            this.summary = false;
            this.products = false;
            this.invoices = false;
            this.transactions = false;
            this.disputes = false;
            this.discountCode = false;
            this.setup = false;
            this.refund = false;
        },
        showRefund() {
            this.refund = true;
            this.sales = false;
            this.summary = false;
            this.products = false;
            this.invoices = false;
            this.transactions = false;
            this.disputes = false;
            this.discountCode = false;
            this.setup = false;
        },
        showInvoices() {
            this.invoices = true;
            this.sales = false;
            this.summary = false;
            this.products = false;
            this.transactions = false;
            this.disputes = false;
            this.discountCode = false;
            this.setup = false;
        },
        showTransactions() {
            this.transactions = true;
            this.summary = false;
            this.products = false;
            this.sales = false;
            this.invoices = false;
            this.disputes = false;
            this.discountCode = false;
            this.setup = false;
        },
        showDisputes() {
            this.disputes = true;
            this.summary = false;
            this.products = false;
            this.sales = false;
            this.invoices = false;
            this.transactions = false;
            this.discountCode = false;
            this.setup = false;
        },
        showDiscountCode() {
            this.search = ''
            this.getAllDiscountCodes();
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-all-products', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.allProducts = res.data.data;
                }
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error';
                this.modalDetail = er;
                this.informModal = true;
            })
        },
        showSetup() {
            this.setup = true;
            this.summary = false;
            this.products = false;
            this.sales = false;
            this.invoices = false;
            this.transactions = false;
            this.disputes = false;
            this.discountCode = false;
        },
        addProductPopup() {
            this.productDetails = null;
            this.addProduct = !this.addProduct;
        },
        acknowledged() {
            this.informModal = false;
        },
        getAllDiscountCodes() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'all-discount-codes', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.items = res.data.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        }
    }

}
</script>
<style scoped>
@import 'datatables.net-dt';

.sideBar {
    background-color: #F7F7F7;
    width: 250px;
    height: 100%;
    overflow-y: scroll;
}

.topBar {
    background-color: #eeeeee;
    width: 100%;
    float: right;
    position: relative;
}

.topbar_btn {
    color: #F2A18C;
    padding: 0.5rem 1rem;
    background-color: black;
    border: none;
}

.topbar_btn_active {
    color: white !important;
    padding: 0.5rem 1rem;
    background-color: #F2A18C !important;
    border: none;
}

.three-dots2 {
    padding: 0px;
    height: 25px;
    border: none;
    background-color: inherit;
    text-align: center;
    float: right;
    width: 25px;
}

/* .three-dots2:after {
    cursor: pointer;
    content: '\2807';
    padding-left: 5px;
} */

.inp1::placeholder {
    color: #B1B0B0;
}

.inp2 {
    width: 380px;
    height: 40px;
    font-size: 13px;
    color: #C5C5C5;
    border: 1px solid #C5C5C5;
}

.inp2::placeholder {
    font-size: 13px;
    color: #C5C5C5;
}

.active {
    font-weight: lighter !important;
    background-color: #EEEEEE !important;
    color: #343434;
}

.active1 {
    background-color: #F2A18C !important;
}

.inp3 {
    width: 400px;
    height: 40px;
    background-color: white;
    font-size: 13px;
    color: #C5C5C5;
    border: 1px solid #C5C5C5;
}

.inp4 {
    border: 1px solid #606060c0;
    color: #606060;
    font-size: 15px;
    width: 340px;
    height: 40px;
    text-align: center;
}

.inp4::placeholder {
    text-align: center;
}
.hvrPro:hover{
    background-color: #e0e0e0;
}
</style>
