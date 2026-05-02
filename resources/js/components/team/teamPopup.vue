<template lang="">
    <div>
        <div class="my-popup-component" @click.self="quitComponent">
            <Loader v-if="pageLoading" :loadingText="loaderText" />
            <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
            <div v-if="userData" class="brds-4 position-relative pb-3" style="height:90vh;width:80%;background-color:white;overflow:auto;">
                <button class="trans_btn float-end" @click="quitComponent" style="right:15px;top:10px;font-size:25px">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div v-if="editTeamMember==true || teamMembersProfile==true" style="width:100%;min-height:100px;position:relative;margin-top:20px;">
                    <div class="position-relative pointer float-start" style="height:90px;width:80px;margin-left:50px;">
                        <button v-if="editTeamMember" class="position-absolute" style="bottom:-15px;right:-10px;background-color:transparent;border:none;font-size:40px;z-index:99 !important;">+</button>
                        <input v-if="editTeamMember" type="file" ref="selectedImage" accept="image/*" @change="checkImage" style="position:absolute;height:100%;width:100%;opacity:0;z-index:99999 !important;cursor:pointer">
                        <img v-if="image_URL" :src="image_URL"  alt="Error" style="width:80px;height:80px;margin-top:10px;float:left;position:relative;border-radius:50%;">
                    </div>
                    <div v-if="userData" class="float-start mt-2 ms-4">
                        <p class="mb-0 ms-2" style="font-size:37px;">{{userData.userdetails.name}} {{userData.userdetails.Lastname}}</p>
                        <p class="mb-0 ms-3" style="font-size:14px;color:#B1B0B0;">{{userData.role}}</p>
                    </div>
                    <p v-if="editTeamMember" class="float-start ms-4" style="font-size:15px;margin-top:40px;">Change Profile Picture</p>
                    <button v-if="!editTeamMember" class="float-start brds-4 tslin" style="position:absolute;top:30px;right:90px;border:none;height:27px;width:115px;font-size:17px;background-color:transparent;color:#B1B0B0;" @click="editTeamMember=true,teamMembersProfile=false">Edit</button>
                    <button v-if="editTeamMember" class="float-start brds-4 tslin" style="position:absolute;top:30px;right:210px;border:none;height:27px;width:115px;font-size:17px;background-color:transparent;color:#B1B0B0;" @click="save()">Save</button>
                    <button v-if="editTeamMember" class="float-start brds-4 tslin" style="position:absolute;top:30px;right:90px;border:none;height:27px;width:115px;font-size:17px;background-color:transparent;color:#B1B0B0;" @click="quitComponent">Cancel</button>
                </div>
                <div v-if="userData!=null&&teamMembersProfile" class="d-flex justify-content-center flex-wrap" style="width:100%;min-height:50px;margin-top:10px;">
                    <div class="float-start" style="margin-left:50px;">
                        <p style="font-size:18px;" class="float-start mb-0 mt-2">me profile</p>
                        <p class="tslin float-start ms-4 brds-5 mb-0" style="height:45px;width:280px;font-size:11px;text-align:center;padding-top:13px;color:#B1B0B0;">fwd.senarios.co/cms/clients/{{userData.userdetails.name}}{{userData.userdetails.Lastname}}</p>
                    </div>
                    <div class="float-start" style="margin-left:50px;">
                        <p style="font-size:18px;" class="float-start mb-0 mt-2">Last signed in:</p>
                        <p class="tslin float-start ms-4 brds-5 mb-0" style="height:45px;width:280px;font-size:11px;text-align:center;padding-top:13px;color:#B1B0B0;">8 May 2020</p>
                    </div>
                </div>
                <div v-if="teamMembersProfile && userData!=null" style="width:100%;min-height:300px;padding-top:15px;display:flex;justify-content:center;padding-bottom:20px;margin-top:10px;">
                    <div class="brds-3 tsl float-start me-2" style="width:430px;min-height:270px;">
                        <div class="w-80 mx-auto mt-3" style="min-height:30px;display:flex;justify-content:space-between">
                            <p class="m-0 pt-1 p1">Email:</p>
                            <p class="m-0 p2">{{userData.email}}</p>
                        </div>
                        <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                            <p class="m-0 pt-1 p1">Phone:</p>
                            <p class="m-0 p2">{{userData.userdetails.phone}}</p>
                        </div>
                        <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                            <p class="m-0 pt-1 p1">DOB: </p>
                            <p class="m-0 p2">{{userData.userdetails.DOB}}</p>
                        </div>
                        <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                            <p class="m-0 pt-1 p1">Sex:</p>
                            <p class="m-0 p2">{{userData.userdetails.gender}}</p>
                        </div>
                        <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                            <p class="m-0 pt-1 p1">Units:</p>
                            <p class="m-0 p2">Weight ({{userData.user_settings.weight_unit}});Distance ({{userData.user_settings.distance_unit}});Body Stats ({{userData.user_settings.body_measures}})</p>
                        </div>
                        <div class="w-80 mx-auto mt-3" style="min-height:30px;display:flex;justify-content:space-between">
                            <p class="m-0 pt-1 p1">Added on:</p>
                            <p class="m-0 p2">{{userData.created_on}}</p>
                        </div>
                    </div>
                    <div class="brds-3 tsl float-start ms-2" style="width:430px;min-height:270px;">
                        <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                            <p class="m-0 pt-1 p1">Assigned Clients:</p>
                            <p class="m-0 p2">{{userData.assigned_clients}}</p>
                        </div>
                        <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                            <p class="m-0 pt-1 p1">Country:</p>
                            <p class="m-0 p2">{{userData.userdetails.country}}</p>
                        </div>
                    </div>
                </div>
                <div v-if="editTeamMember" class="tsl brds-2 mx-auto pt-3 position-relative" style="height:580px;width:90%;">
                    <div class="col-11 mx-auto d-flex flex-wrap justify-content-start justify-content-xl-around h-100">
                        <div class="w-100">
                            <p class="text-danger mb-0 ms-0">All the fields with * are required please fill them all.</p>
                        </div>
                        <div class="col-xl-6 col-md-8 position-relative px-2" style="height:35px;">
                            <p class="float-start mt-1" style="font-size:15px;width:100px;">First Name<span class="text-danger">*</span></p>
                            <input class="tslin float-end brds-4 border-0 px-3" v-model="userDataToEdit.userdetails.name" type="text" style="height:35px;width:calc(100% - 100px);font-size:15px;">
                        </div>
                        <div class="col-xl-6 col-md-8 position-relative px-2" style="height:35px;">
                            <p class="float-start mt-1" style="font-size:15px;width:100px;">Last Name<span class="text-danger">*</span></p>
                            <input class="tslin float-end brds-4 border-0 px-3" v-model="userDataToEdit.userdetails.Lastname" type="text" style="height:35px;width:calc(100% - 100px);font-size:15px;">
                        </div>
                        <div class="col-xl-12 col-md-8 px-2" style="height:35px;">
                            <p class="float-start mt-1" style="font-size:15px;width:100px;">Role<span class="text-danger">*</span></p>
                            <div class="tslin float-end brds-4 px-3" style="width:calc(100% - 100px)">
                                <select class="brds-4 border-0" v-model="userDataToEdit.role" style="height:35px;width:100%;font-size:15px;background-color:transparent;">
                                    <option value="trainer">Trainer</option>
                                    <option value="manager">Manager</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-8 position-relative px-2" style="height:35px;">
                            <p class="float-start mt-1" style="font-size:15px;width:100px;">Email<span class="text-danger">*</span></p>
                            <input class="tslin float-end brds-4 border-0 px-3" v-model="userDataToEdit.email" type="email" required style="height:35px;width:calc(100% - 100px);font-size:15px;">
                        </div>
                        <div class="col-xl-6 col-md-8 position-relative px-2" style="height:35px;">
                            <p class="float-start mt-1" style="font-size:15px;width:100px;">Phone No<span class="text-danger">*</span></p>
                            <input class="tslin float-end brds-4 border-0 px-3" @keyup="checkPhoneNo" v-model="userDataToEdit.userdetails.phone" type="text" style="height:35px;width:calc(100% - 100px);font-size:15px;">
                        </div>
                        <div class="col-xl-6 col-md-8 position-relative px-2" style="height:35px;">
                            <p class="float-start mt-1" style="font-size:15px;width:100px;">Country<span class="text-danger">*</span></p>
                            <div class="tslin float-end brds-4 px-2" style="width:calc(100% - 100px)">
                                <select class="brds-4 border-0 selectpicker countrypicker" v-model="userDataToEdit.userdetails.country" style="height:35px;width:100%;font-size:15px;background-color:transparent;">
                                    <option value="Afghanistan">Afghanistan</option>
                                    <option value="Albania">Albania</option>
                                    <option value="Algeria">Algeria</option>
                                    <option value="American Samoa">American Samoa</option>
                                    <option value="Andorra">Andorra</option>
                                    <option value="Angola">Angola</option>
                                    <option value="Anguilla">Anguilla</option>
                                    <option value="Antartica">Antarctica</option>
                                    <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                                    <option value="Argentina">Argentina</option>
                                    <option value="Armenia">Armenia</option>
                                    <option value="Aruba">Aruba</option>
                                    <option value="Australia">Australia</option>
                                    <option value="Austria">Austria</option>
                                    <option value="Azerbaijan">Azerbaijan</option>
                                    <option value="Bahamas">Bahamas</option>
                                    <option value="Bahrain">Bahrain</option>
                                    <option value="Bangladesh">Bangladesh</option>
                                    <option value="Barbados">Barbados</option>
                                    <option value="Belarus">Belarus</option>
                                    <option value="Belgium">Belgium</option>
                                    <option value="Belize">Belize</option>
                                    <option value="Benin">Benin</option>
                                    <option value="Bermuda">Bermuda</option>
                                    <option value="Bhutan">Bhutan</option>
                                    <option value="Bolivia">Bolivia</option>
                                    <option value="Bosnia and Herzegowina">Bosnia and Herzegowina</option>
                                    <option value="Botswana">Botswana</option>
                                    <option value="Bouvet Island">Bouvet Island</option>
                                    <option value="Brazil">Brazil</option>
                                    <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>
                                    <option value="Brunei Darussalam">Brunei Darussalam</option>
                                    <option value="Bulgaria">Bulgaria</option>
                                    <option value="Burkina Faso">Burkina Faso</option>
                                    <option value="Burundi">Burundi</option>
                                    <option value="Cambodia">Cambodia</option>
                                    <option value="Cameroon">Cameroon</option>
                                    <option value="Canada">Canada</option>
                                    <option value="Cape Verde">Cape Verde</option>
                                    <option value="Cayman Islands">Cayman Islands</option>
                                    <option value="Central African Republic">Central African Republic</option>
                                    <option value="Chad">Chad</option>
                                    <option value="Chile">Chile</option>
                                    <option value="China">China</option>
                                    <option value="Christmas Island">Christmas Island</option>
                                    <option value="Cocos Islands">Cocos (Keeling) Islands</option>
                                    <option value="Colombia">Colombia</option>
                                    <option value="Comoros">Comoros</option>
                                    <option value="Congo">Congo</option>
                                    <option value="Congo">Congo, the Democratic Republic of the</option>
                                    <option value="Cook Islands">Cook Islands</option>
                                    <option value="Costa Rica">Costa Rica</option>
                                    <option value="Cota D'Ivoire">Cote d'Ivoire</option>
                                    <option value="Croatia">Croatia (Hrvatska)</option>
                                    <option value="Cuba">Cuba</option>
                                    <option value="Cyprus">Cyprus</option>
                                    <option value="Czech Republic">Czech Republic</option>
                                    <option value="Denmark">Denmark</option>
                                    <option value="Djibouti">Djibouti</option>
                                    <option value="Dominica">Dominica</option>
                                    <option value="Dominican Republic">Dominican Republic</option>
                                    <option value="East Timor">East Timor</option>
                                    <option value="Ecuador">Ecuador</option>
                                    <option value="Egypt">Egypt</option>
                                    <option value="El Salvador">El Salvador</option>
                                    <option value="Equatorial Guinea">Equatorial Guinea</option>
                                    <option value="Eritrea">Eritrea</option>
                                    <option value="Estonia">Estonia</option>
                                    <option value="Ethiopia">Ethiopia</option>
                                    <option value="Falkland Islands">Falkland Islands (Malvinas)</option>
                                    <option value="Faroe Islands">Faroe Islands</option>
                                    <option value="Fiji">Fiji</option>
                                    <option value="Finland">Finland</option>
                                    <option value="France">France</option>
                                    <option value="France Metropolitan">France, Metropolitan</option>
                                    <option value="French Guiana">French Guiana</option>
                                    <option value="French Polynesia">French Polynesia</option>
                                    <option value="French Southern Territories">French Southern Territories</option>
                                    <option value="Gabon">Gabon</option>
                                    <option value="Gambia">Gambia</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Germany">Germany</option>
                                    <option value="Ghana">Ghana</option>
                                    <option value="Gibraltar">Gibraltar</option>
                                    <option value="Greece">Greece</option>
                                    <option value="Greenland">Greenland</option>
                                    <option value="Grenada">Grenada</option>
                                    <option value="Guadeloupe">Guadeloupe</option>
                                    <option value="Guam">Guam</option>
                                    <option value="Guatemala">Guatemala</option>
                                    <option value="Guinea">Guinea</option>
                                    <option value="Guinea-Bissau">Guinea-Bissau</option>
                                    <option value="Guyana">Guyana</option>
                                    <option value="Haiti">Haiti</option>
                                    <option value="Heard and McDonald Islands">Heard and Mc Donald Islands</option>
                                    <option value="Holy See">Holy See (Vatican City State)</option>
                                    <option value="Honduras">Honduras</option>
                                    <option value="Hong Kong">Hong Kong</option>
                                    <option value="Hungary">Hungary</option>
                                    <option value="Iceland">Iceland</option>
                                    <option value="India">India</option>
                                    <option value="Indonesia">Indonesia</option>
                                    <option value="Iran">Iran (Islamic Republic of)</option>
                                    <option value="Iraq">Iraq</option>
                                    <option value="Ireland">Ireland</option>
                                    <option value="Israel">Israel</option>
                                    <option value="Italy">Italy</option>
                                    <option value="Jamaica">Jamaica</option>
                                    <option value="Japan">Japan</option>
                                    <option value="Jordan">Jordan</option>
                                    <option value="Kazakhstan">Kazakhstan</option>
                                    <option value="Kenya">Kenya</option>
                                    <option value="Kiribati">Kiribati</option>
                                    <option value="Democratic People's Republic of Korea">Korea, Democratic People's Republic of</option>
                                    <option value="Korea">Korea, Republic of</option>
                                    <option value="Kuwait">Kuwait</option>
                                    <option value="Kyrgyzstan">Kyrgyzstan</option>
                                    <option value="Lao">Lao People's Democratic Republic</option>
                                    <option value="Latvia">Latvia</option>
                                    <option value="Lebanon" selected>Lebanon</option>
                                    <option value="Lesotho">Lesotho</option>
                                    <option value="Liberia">Liberia</option>
                                    <option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option>
                                    <option value="Liechtenstein">Liechtenstein</option>
                                    <option value="Lithuania">Lithuania</option>
                                    <option value="Luxembourg">Luxembourg</option>
                                    <option value="Macau">Macau</option>
                                    <option value="Macedonia">Macedonia, The Former Yugoslav Republic of</option>
                                    <option value="Madagascar">Madagascar</option>
                                    <option value="Malawi">Malawi</option>
                                    <option value="Malaysia">Malaysia</option>
                                    <option value="Maldives">Maldives</option>
                                    <option value="Mali">Mali</option>
                                    <option value="Malta">Malta</option>
                                    <option value="Marshall Islands">Marshall Islands</option>
                                    <option value="Martinique">Martinique</option>
                                    <option value="Mauritania">Mauritania</option>
                                    <option value="Mauritius">Mauritius</option>
                                    <option value="Mayotte">Mayotte</option>
                                    <option value="Mexico">Mexico</option>
                                    <option value="Micronesia">Micronesia, Federated States of</option>
                                    <option value="Moldova">Moldova, Republic of</option>
                                    <option value="Monaco">Monaco</option>
                                    <option value="Mongolia">Mongolia</option>
                                    <option value="Montserrat">Montserrat</option>
                                    <option value="Morocco">Morocco</option>
                                    <option value="Mozambique">Mozambique</option>
                                    <option value="Myanmar">Myanmar</option>
                                    <option value="Namibia">Namibia</option>
                                    <option value="Nauru">Nauru</option>
                                    <option value="Nepal">Nepal</option>
                                    <option value="Netherlands">Netherlands</option>
                                    <option value="Netherlands Antilles">Netherlands Antilles</option>
                                    <option value="New Caledonia">New Caledonia</option>
                                    <option value="New Zealand">New Zealand</option>
                                    <option value="Nicaragua">Nicaragua</option>
                                    <option value="Niger">Niger</option>
                                    <option value="Nigeria">Nigeria</option>
                                    <option value="Niue">Niue</option>
                                    <option value="Norfolk Island">Norfolk Island</option>
                                    <option value="Northern Mariana Islands">Northern Mariana Islands</option>
                                    <option value="Norway">Norway</option>
                                    <option value="Oman">Oman</option>
                                    <option value="Pakistan">Pakistan</option>
                                    <option value="Palau">Palau</option>
                                    <option value="Panama">Panama</option>
                                    <option value="Papua New Guinea">Papua New Guinea</option>
                                    <option value="Paraguay">Paraguay</option>
                                    <option value="Peru">Peru</option>
                                    <option value="Philippines">Philippines</option>
                                    <option value="Pitcairn">Pitcairn</option>
                                    <option value="Poland">Poland</option>
                                    <option value="Portugal">Portugal</option>
                                    <option value="Puerto Rico">Puerto Rico</option>
                                    <option value="Qatar">Qatar</option>
                                    <option value="Reunion">Reunion</option>
                                    <option value="Romania">Romania</option>
                                    <option value="Russia">Russian Federation</option>
                                    <option value="Rwanda">Rwanda</option>
                                    <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                                    <option value="Saint LUCIA">Saint LUCIA</option>
                                    <option value="Saint Vincent">Saint Vincent and the Grenadines</option>
                                    <option value="Samoa">Samoa</option>
                                    <option value="San Marino">San Marino</option>
                                    <option value="Sao Tome and Principe">Sao Tome and Principe</option>
                                    <option value="Saudi Arabia">Saudi Arabia</option>
                                    <option value="Senegal">Senegal</option>
                                    <option value="Seychelles">Seychelles</option>
                                    <option value="Sierra">Sierra Leone</option>
                                    <option value="Singapore">Singapore</option>
                                    <option value="Slovakia">Slovakia (Slovak Republic)</option>
                                    <option value="Slovenia">Slovenia</option>
                                    <option value="Solomon Islands">Solomon Islands</option>
                                    <option value="Somalia">Somalia</option>
                                    <option value="South Africa">South Africa</option>
                                    <option value="South Georgia">South Georgia and the South Sandwich Islands</option>
                                    <option value="Span">Spain</option>
                                    <option value="SriLanka">Sri Lanka</option>
                                    <option value="St. Helena">St. Helena</option>
                                    <option value="St. Pierre and Miguelon">St. Pierre and Miquelon</option>
                                    <option value="Sudan">Sudan</option>
                                    <option value="Suriname">Suriname</option>
                                    <option value="Svalbard">Svalbard and Jan Mayen Islands</option>
                                    <option value="Swaziland">Swaziland</option>
                                    <option value="Sweden">Sweden</option>
                                    <option value="Switzerland">Switzerland</option>
                                    <option value="Syria">Syrian Arab Republic</option>
                                    <option value="Taiwan">Taiwan, Province of China</option>
                                    <option value="Tajikistan">Tajikistan</option>
                                    <option value="Tanzania">Tanzania, United Republic of</option>
                                    <option value="Thailand">Thailand</option>
                                    <option value="Togo">Togo</option>
                                    <option value="Tokelau">Tokelau</option>
                                    <option value="Tonga">Tonga</option>
                                    <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                                    <option value="Tunisia">Tunisia</option>
                                    <option value="Turkey">Turkey</option>
                                    <option value="Turkmenistan">Turkmenistan</option>
                                    <option value="Turks and Caicos">Turks and Caicos Islands</option>
                                    <option value="Tuvalu">Tuvalu</option>
                                    <option value="Uganda">Uganda</option>
                                    <option value="Ukraine">Ukraine</option>
                                    <option value="United Arab Emirates">United Arab Emirates</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                    <option value="United States">United States</option>
                                    <option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>
                                    <option value="Uruguay">Uruguay</option>
                                    <option value="Uzbekistan">Uzbekistan</option>
                                    <option value="Vanuatu">Vanuatu</option>
                                    <option value="Venezuela">Venezuela</option>
                                    <option value="Vietnam">Viet Nam</option>
                                    <option value="Virgin Islands (British)">Virgin Islands (British)</option>
                                    <option value="Virgin Islands (U.S)">Virgin Islands (U.S.)</option>
                                    <option value="Wallis and Futana Islands">Wallis and Futuna Islands</option>
                                    <option value="Western Sahara">Western Sahara</option>
                                    <option value="Yemen">Yemen</option>
                                    <option value="Serbia">Serbia</option>
                                    <option value="Zambia">Zambia</option>
                                    <option value="Zimbabwe">Zimbabwe</option>
                                </select>
                            </div>
                            <!-- <input class="tslin float-end brds-4 border-0 px-3" v-model="userDataToEdit.userdetails.country" type="text" style="height:35px;width:300px;font-size:15px;"> -->
                        </div>
                        <div class="col-xl-6 col-md-8 position-relative px-2" style="height:35px;">
                            <p class="float-start mt-1" style="font-size:15px;width:100px;">Gender<span class="text-danger">*</span></p>
                            <div class="tslin brds-4 px-2 float-end" style="width:calc(100% - 100px);">
                                <select class="brds-4 border-0" v-model="userDataToEdit.userdetails.gender" style="height:35px;width:100%;font-size:15px;background-color:transparent;">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-8 position-relative px-2" style="height:35px;">
                            <p class="float-start mt-1" style="font-size:15px;width:100px;">DOB<span class="text-danger">*</span></p>
                            <div style="width:calc(100% - 100px);float:left;height:35px;border-radius:20px;border:none;box-shadow:  0 0 5px 0px #F2A18C inset;">
                                <Datepicker v-model="dateEntered" :startDate="maxDate" :maxDate="maxDate" :format="format" autoApply ignoreTimeValidation :enableTimePicker="false"/>
                            </div>
                            <!-- <date-picker style="height:35px;width:300px;" v-model:value="userDataToEdit.userdetails.DOB" valueType="format"></date-picker> -->
                        </div>
                        <div class="col-xl-6 col-md-8 position-relative px-2" style="height:35px;">
                            <p class="float-start mt-1" style="font-size:15px;width:100px;">Weight Units<span class="text-danger">*</span></p>
                            <div class="tslin brds-4 px-2 float-end" style="width:calc(100% - 100px);">
                                <select class="brds-4 border-0" v-model="userDataToEdit.user_settings.weight_unit" style="height:35px;width:100%;font-size:15px;background-color:transparent;">
                                    <option value="kg">KG</option>
                                    <option value="lbs">LB</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-8 position-relative px-2" style="height:35px;">
                            <p class="float-start mt-1" style="font-size:15px;width:100px;">Distance Units<span class="text-danger">*</span></p>
                            <div class="brds-4 tslin px-2 float-end" style="width:calc(100% - 100px);">
                                <select class="brds-4 border-0" v-model="userDataToEdit.user_settings.distance_unit" style="height:35px;width:100%;font-size:15px;background-color:transparent;">
                                    <option value="km">KM</option>
                                    <option value="miles">Miles</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-8 position-relative px-2" style="height:35px;">
                            <p class="float-start mt-1" style="font-size:15px;width:100px;">Body stat Units<span class="text-danger">*</span></p>
                            <div class="brds-4 tslin px-2 float-end" style="width:calc(100% - 100px);">
                                <select class="brds-4 border-0" v-model="userDataToEdit.user_settings.body_measures" style="height:35px;width:100%;font-size:15px;background-color:transparent;">
                                    <option value="cm">CM</option>
                                    <option value="inches">Inches</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../loader.vue';
import Inform from '../inform.vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
// import DatePicker from 'vue-datepicker-next';
export default {
    components: { Loader, Inform, Datepicker },
    props: ['user_id'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            userData: null,
            userDataToEdit: null,
            pageLoading: false,
            loaderText: '',
            modalTitle: '',
            modalDetail: '',
            informModal: false,
            editTeamMember: false,
            teamMembersProfile: true,
            image_URL: null,
            imageError: false,
            img: null,
            maxDate: null,
            dateEntered: null,
            nmbrError: false,
        }
    },
    mounted() {
        this.pageLoading = true;
        axios.get(config.baseApiUrl + 'team-member-detail/' + this.user_id, this.apiConfig).then(res => {
            this.pageLoading = false;
            if (res.data.status) {
                this.userData = res.data.data;
                this.userDataToEdit = res.data.data;
                this.image_URL = this.userData.userdetails.picture;
                this.dateEntered = this.userDataToEdit.userdetails.DOB
            }
            else {
                this.modalTitle = 'Error!';
                this.modalDetail = res.data.message;
                this.informModal = true;
            }
        }).catch(er => {
            this.pageLoading = false;
            this.modalTitle = 'Error!';
            this.modalDetail = er;
            this.informModal = true;
        })
        let today = new Date();
        today.setDate(today.getDate() - 3653);
        this.maxDate = today;
    },
    methods: {
        checkPhoneNo() {
            let length = this.userDataToEdit.userdetails.phone.length;
            if (length == 1) {
                if (this.userDataToEdit.userdetails.phone.includes("+", 0)) {
                    this.nmbrError = false;
                }
                else {
                    this.userDataToEdit.userdetails.phone = '+' + this.userDataToEdit.userdetails.phone;
                }
            }
            else {
                if (this.userDataToEdit.userdetails.phone.includes("0", length - 1) || this.userDataToEdit.userdetails.phone.includes("1", length - 1) || this.userDataToEdit.userdetails.phone.includes("2", length - 1) ||
                    this.userDataToEdit.userdetails.phone.includes("3", length - 1) || this.userDataToEdit.userdetails.phone.includes("4", length - 1) || this.userDataToEdit.userdetails.phone.includes("5", length - 1) || this.userDataToEdit.userdetails.phone.includes("6", length - 1) ||
                    this.userDataToEdit.userdetails.phone.includes("7", length - 1) || this.userDataToEdit.userdetails.phone.includes("8", length - 1) || this.userDataToEdit.userdetails.phone.includes("9", length - 1)) {
                    this.nmbrError = false;
                }
                else {
                    this.userDataToEdit.userdetails.phone = this.userDataToEdit.userdetails.phone.slice(0, - 1);
                }
            }
        },
        quitComponent() {
            this.$parent.openDetails();
        },
        save() {
            let phoneNoConfirmation = this.userDataToEdit.userdetails.phone;
            if (this.dateEntered != null && this.dateEntered != this.userData.userdetails.DOB) {
                let days = this.dateEntered.getDate();
                let months = this.dateEntered.getMonth() + 1;
                let years = this.dateEntered.getFullYear();
                this.userDataToEdit.userdetails.DOB = months + '/' + days + '/' + years;
            }
            let error = false;
            if (this.imageError == true) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please select an Image';
                this.informModal = true;
                error = true;
            }
            if (this.userDataToEdit.email != null || this.userDataToEdit.email == '') {
                var validRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
                if (!this.userDataToEdit.email.match(validRegex)) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Not a valid Email';
                    this.informModal = true;
                    error = true;
                }
            }
            if (this.userDataToEdit.userdetails.name == '' || this.userData.userdetails.Lastname == '' ||
                this.userData.userdetails.phone == '' || this.userData.userdetails.country == '' || this.userData.userdetails.DOB == '') {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please fill all the fields with *'
                this.informModal = true;
                error = true;
            }
            if (phoneNoConfirmation.toString().length < 8 || phoneNoConfirmation.toString().length > 16) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Not a valid phone number. It must be between 7 to 15 digits excluding "+"';
                this.informModal = true;
                error = true;
            }
            if (error == true || this.nmbrError == true) {
                return
            }
            else {
                let fd = new FormData();
                if (this.image_URL != this.userData.userdetails.picture) {
                    fd.append('picture', this.img)
                }
                fd.append('first_name', this.userDataToEdit.userdetails.name);
                fd.append('last_name', this.userDataToEdit.userdetails.Lastname);
                fd.append('role', this.userDataToEdit.role);
                fd.append('email', this.userDataToEdit.email);
                fd.append('phone', this.userDataToEdit.userdetails.phone);
                fd.append('country', this.userDataToEdit.userdetails.country);
                fd.append('gender', this.userDataToEdit.userdetails.gender);
                fd.append('dob', this.userDataToEdit.userdetails.DOB);
                fd.append('weight_unit', this.userDataToEdit.user_settings.weight_unit);
                fd.append('distance_unit', this.userDataToEdit.user_settings.distance_unit);
                fd.append('body_stat_unit', this.userDataToEdit.user_settings.body_measures);
                fd.append('member_id', this.user_id)
                this.pageLoading = true;
                this.loaderText = 'Loading';
                axios.post(config.baseApiUrl + 'edit-team-member', fd, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done';
                        this.modalDetail = 'Team member created successfully';
                        this.informModal = true;
                        this.$parent.getTeam();
                        this.$parent.openDetails();
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = er;
                    this.informModal = true;
                })
            }
        },
        checkImage() {
            this.img = this.$refs.selectedImage.files[0];
            if (!this.img.type.includes("image")) {
                this.imageError = true;
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please select an image file';
                this.informModal = true;
            }
            else {
                this.imageError = false;
                this.image_URL = window.URL.createObjectURL(this.img);
            }
        },
        acknowledged() {
            this.informModal = false;
        }
    }
}
</script>
<style scoped>
.p1 {
    font-size: 15px;
    float: left;
    width: 130px;
}

.p2 {
    font-size: 15px;
    float: left;
    width: 190px;
    padding: 6px 0px 5px 10px;
    color: #B1B0B0
}

.active {
    background-color: #F2A18C !important;
    border: none !important;
}

.dp__input {
    height: 35px !important;
    border-radius: 20px !important;
    border: none !important;
    box-shadow: 0 0 5px 0px #F2A18C inset !important;
}

.dp__input_wrap {
    width: 300px !important;
    float: left !important;

}
</style>
