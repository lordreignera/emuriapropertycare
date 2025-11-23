@extends('client.layout')

@section('title', 'Add New Property')

@section('header', 'Add New Property')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('client.properties.index') }}">Properties</a></li>
<li class="breadcrumb-item active" aria-current="page">Add New</li>
@endsection

@section('content')
<form action="{{ route('client.properties.store') }}" method="POST" enctype="multipart/form-data" id="propertyForm">
    @csrf
    
    <div class="row">
        <div class="col-12">
            {{-- Property Information --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="mdi mdi-home-modern text-primary"></i> Property Information
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="property_name">Property Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('property_name') is-invalid @enderror" 
                                    id="property_name" name="property_name" value="{{ old('property_name') }}" 
                                    placeholder="e.g., Sunset Villa" required>
                                @error('property_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="property_brand">Property Brand <small class="text-muted">(Optional)</small></label>
                                <input type="text" class="form-control @error('property_brand') is-invalid @enderror" 
                                    id="property_brand" name="property_brand" value="{{ old('property_brand') }}" 
                                    placeholder="e.g., Sunset, Maple, Victoria"
                                    maxlength="20">
                                <small class="form-text text-muted">Used to generate property code prefix</small>
                                @error('property_brand')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="property_code_preview">Property Code Preview</label>
                                <input type="text" class="form-control" 
                                    id="property_code_preview" 
                                    readonly 
                                    style="background-color: #f8f9fa; font-weight: bold; color: #495057;"
                                    value="PROP-{{ substr(time(), 0, 10) }}">
                                <small class="form-text text-muted">Auto-generated unique identifier for your property</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type">Property Type <span class="text-danger">*</span></label>
                                <select class="form-control @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="house" {{ old('type') == 'house' ? 'selected' : '' }}>House</option>
                                    <option value="townhome" {{ old('type') == 'townhome' ? 'selected' : '' }}>Townhome</option>
                                    <option value="condo" {{ old('type') == 'condo' ? 'selected' : '' }}>Condo</option>
                                    <option value="duplex" {{ old('type') == 'duplex' ? 'selected' : '' }}>Duplex</option>
                                    <option value="multi-unit" {{ old('type') == 'multi-unit' ? 'selected' : '' }}>Multi-Unit</option>
                                </select>
                                @error('type')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="year_built">Year Built</label>
                                <select class="form-control @error('year_built') is-invalid @enderror" 
                                    id="year_built" name="year_built">
                                    <option value="">Select Year</option>
                                    @for($year = date('Y'); $year >= 1800; $year--)
                                        <option value="{{ $year }}" {{ old('year_built') == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endfor
                                </select>
                                @error('year_built')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Address Information --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="mdi mdi-map-marker text-success"></i> Address Information
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="property_address">Street Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('property_address') is-invalid @enderror" 
                                    id="property_address" name="property_address" value="{{ old('property_address') }}" 
                                    placeholder="123 Main Street" required>
                                @error('property_address')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="city">City <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                    id="city" name="city" value="{{ old('city') }}" required>
                                @error('city')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="province">Province/State <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('province') is-invalid @enderror" 
                                    id="province" name="province" value="{{ old('province') }}" required>
                                @error('province')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="postal_code">Postal/ZIP Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" 
                                    id="postal_code" name="postal_code" value="{{ old('postal_code') }}" required>
                                @error('postal_code')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="country">Country <span class="text-danger">*</span></label>
                                <select class="form-control @error('country') is-invalid @enderror" id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="Afghanistan" {{ old('country') == 'Afghanistan' ? 'selected' : '' }}>Afghanistan</option>
                                    <option value="Albania" {{ old('country') == 'Albania' ? 'selected' : '' }}>Albania</option>
                                    <option value="Algeria" {{ old('country') == 'Algeria' ? 'selected' : '' }}>Algeria</option>
                                    <option value="Andorra" {{ old('country') == 'Andorra' ? 'selected' : '' }}>Andorra</option>
                                    <option value="Angola" {{ old('country') == 'Angola' ? 'selected' : '' }}>Angola</option>
                                    <option value="Argentina" {{ old('country') == 'Argentina' ? 'selected' : '' }}>Argentina</option>
                                    <option value="Armenia" {{ old('country') == 'Armenia' ? 'selected' : '' }}>Armenia</option>
                                    <option value="Australia" {{ old('country') == 'Australia' ? 'selected' : '' }}>Australia</option>
                                    <option value="Austria" {{ old('country') == 'Austria' ? 'selected' : '' }}>Austria</option>
                                    <option value="Azerbaijan" {{ old('country') == 'Azerbaijan' ? 'selected' : '' }}>Azerbaijan</option>
                                    <option value="Bahamas" {{ old('country') == 'Bahamas' ? 'selected' : '' }}>Bahamas</option>
                                    <option value="Bahrain" {{ old('country') == 'Bahrain' ? 'selected' : '' }}>Bahrain</option>
                                    <option value="Bangladesh" {{ old('country') == 'Bangladesh' ? 'selected' : '' }}>Bangladesh</option>
                                    <option value="Barbados" {{ old('country') == 'Barbados' ? 'selected' : '' }}>Barbados</option>
                                    <option value="Belarus" {{ old('country') == 'Belarus' ? 'selected' : '' }}>Belarus</option>
                                    <option value="Belgium" {{ old('country') == 'Belgium' ? 'selected' : '' }}>Belgium</option>
                                    <option value="Belize" {{ old('country') == 'Belize' ? 'selected' : '' }}>Belize</option>
                                    <option value="Benin" {{ old('country') == 'Benin' ? 'selected' : '' }}>Benin</option>
                                    <option value="Bhutan" {{ old('country') == 'Bhutan' ? 'selected' : '' }}>Bhutan</option>
                                    <option value="Bolivia" {{ old('country') == 'Bolivia' ? 'selected' : '' }}>Bolivia</option>
                                    <option value="Bosnia and Herzegovina" {{ old('country') == 'Bosnia and Herzegovina' ? 'selected' : '' }}>Bosnia and Herzegovina</option>
                                    <option value="Botswana" {{ old('country') == 'Botswana' ? 'selected' : '' }}>Botswana</option>
                                    <option value="Brazil" {{ old('country') == 'Brazil' ? 'selected' : '' }}>Brazil</option>
                                    <option value="Brunei" {{ old('country') == 'Brunei' ? 'selected' : '' }}>Brunei</option>
                                    <option value="Bulgaria" {{ old('country') == 'Bulgaria' ? 'selected' : '' }}>Bulgaria</option>
                                    <option value="Burkina Faso" {{ old('country') == 'Burkina Faso' ? 'selected' : '' }}>Burkina Faso</option>
                                    <option value="Burundi" {{ old('country') == 'Burundi' ? 'selected' : '' }}>Burundi</option>
                                    <option value="Cambodia" {{ old('country') == 'Cambodia' ? 'selected' : '' }}>Cambodia</option>
                                    <option value="Cameroon" {{ old('country') == 'Cameroon' ? 'selected' : '' }}>Cameroon</option>
                                    <option value="Canada" {{ old('country') == 'Canada' ? 'selected' : '' }}>Canada</option>
                                    <option value="Cape Verde" {{ old('country') == 'Cape Verde' ? 'selected' : '' }}>Cape Verde</option>
                                    <option value="Central African Republic" {{ old('country') == 'Central African Republic' ? 'selected' : '' }}>Central African Republic</option>
                                    <option value="Chad" {{ old('country') == 'Chad' ? 'selected' : '' }}>Chad</option>
                                    <option value="Chile" {{ old('country') == 'Chile' ? 'selected' : '' }}>Chile</option>
                                    <option value="China" {{ old('country') == 'China' ? 'selected' : '' }}>China</option>
                                    <option value="Colombia" {{ old('country') == 'Colombia' ? 'selected' : '' }}>Colombia</option>
                                    <option value="Comoros" {{ old('country') == 'Comoros' ? 'selected' : '' }}>Comoros</option>
                                    <option value="Congo" {{ old('country') == 'Congo' ? 'selected' : '' }}>Congo</option>
                                    <option value="Costa Rica" {{ old('country') == 'Costa Rica' ? 'selected' : '' }}>Costa Rica</option>
                                    <option value="Croatia" {{ old('country') == 'Croatia' ? 'selected' : '' }}>Croatia</option>
                                    <option value="Cuba" {{ old('country') == 'Cuba' ? 'selected' : '' }}>Cuba</option>
                                    <option value="Cyprus" {{ old('country') == 'Cyprus' ? 'selected' : '' }}>Cyprus</option>
                                    <option value="Czech Republic" {{ old('country') == 'Czech Republic' ? 'selected' : '' }}>Czech Republic</option>
                                    <option value="Denmark" {{ old('country') == 'Denmark' ? 'selected' : '' }}>Denmark</option>
                                    <option value="Djibouti" {{ old('country') == 'Djibouti' ? 'selected' : '' }}>Djibouti</option>
                                    <option value="Dominica" {{ old('country') == 'Dominica' ? 'selected' : '' }}>Dominica</option>
                                    <option value="Dominican Republic" {{ old('country') == 'Dominican Republic' ? 'selected' : '' }}>Dominican Republic</option>
                                    <option value="Ecuador" {{ old('country') == 'Ecuador' ? 'selected' : '' }}>Ecuador</option>
                                    <option value="Egypt" {{ old('country') == 'Egypt' ? 'selected' : '' }}>Egypt</option>
                                    <option value="El Salvador" {{ old('country') == 'El Salvador' ? 'selected' : '' }}>El Salvador</option>
                                    <option value="Equatorial Guinea" {{ old('country') == 'Equatorial Guinea' ? 'selected' : '' }}>Equatorial Guinea</option>
                                    <option value="Eritrea" {{ old('country') == 'Eritrea' ? 'selected' : '' }}>Eritrea</option>
                                    <option value="Estonia" {{ old('country') == 'Estonia' ? 'selected' : '' }}>Estonia</option>
                                    <option value="Ethiopia" {{ old('country') == 'Ethiopia' ? 'selected' : '' }}>Ethiopia</option>
                                    <option value="Fiji" {{ old('country') == 'Fiji' ? 'selected' : '' }}>Fiji</option>
                                    <option value="Finland" {{ old('country') == 'Finland' ? 'selected' : '' }}>Finland</option>
                                    <option value="France" {{ old('country') == 'France' ? 'selected' : '' }}>France</option>
                                    <option value="Gabon" {{ old('country') == 'Gabon' ? 'selected' : '' }}>Gabon</option>
                                    <option value="Gambia" {{ old('country') == 'Gambia' ? 'selected' : '' }}>Gambia</option>
                                    <option value="Georgia" {{ old('country') == 'Georgia' ? 'selected' : '' }}>Georgia</option>
                                    <option value="Germany" {{ old('country') == 'Germany' ? 'selected' : '' }}>Germany</option>
                                    <option value="Ghana" {{ old('country') == 'Ghana' ? 'selected' : '' }}>Ghana</option>
                                    <option value="Greece" {{ old('country') == 'Greece' ? 'selected' : '' }}>Greece</option>
                                    <option value="Grenada" {{ old('country') == 'Grenada' ? 'selected' : '' }}>Grenada</option>
                                    <option value="Guatemala" {{ old('country') == 'Guatemala' ? 'selected' : '' }}>Guatemala</option>
                                    <option value="Guinea" {{ old('country') == 'Guinea' ? 'selected' : '' }}>Guinea</option>
                                    <option value="Guinea-Bissau" {{ old('country') == 'Guinea-Bissau' ? 'selected' : '' }}>Guinea-Bissau</option>
                                    <option value="Guyana" {{ old('country') == 'Guyana' ? 'selected' : '' }}>Guyana</option>
                                    <option value="Haiti" {{ old('country') == 'Haiti' ? 'selected' : '' }}>Haiti</option>
                                    <option value="Honduras" {{ old('country') == 'Honduras' ? 'selected' : '' }}>Honduras</option>
                                    <option value="Hungary" {{ old('country') == 'Hungary' ? 'selected' : '' }}>Hungary</option>
                                    <option value="Iceland" {{ old('country') == 'Iceland' ? 'selected' : '' }}>Iceland</option>
                                    <option value="India" {{ old('country') == 'India' ? 'selected' : '' }}>India</option>
                                    <option value="Indonesia" {{ old('country') == 'Indonesia' ? 'selected' : '' }}>Indonesia</option>
                                    <option value="Iran" {{ old('country') == 'Iran' ? 'selected' : '' }}>Iran</option>
                                    <option value="Iraq" {{ old('country') == 'Iraq' ? 'selected' : '' }}>Iraq</option>
                                    <option value="Ireland" {{ old('country') == 'Ireland' ? 'selected' : '' }}>Ireland</option>
                                    <option value="Israel" {{ old('country') == 'Israel' ? 'selected' : '' }}>Israel</option>
                                    <option value="Italy" {{ old('country') == 'Italy' ? 'selected' : '' }}>Italy</option>
                                    <option value="Jamaica" {{ old('country') == 'Jamaica' ? 'selected' : '' }}>Jamaica</option>
                                    <option value="Japan" {{ old('country') == 'Japan' ? 'selected' : '' }}>Japan</option>
                                    <option value="Jordan" {{ old('country') == 'Jordan' ? 'selected' : '' }}>Jordan</option>
                                    <option value="Kazakhstan" {{ old('country') == 'Kazakhstan' ? 'selected' : '' }}>Kazakhstan</option>
                                    <option value="Kenya" {{ old('country') == 'Kenya' ? 'selected' : '' }}>Kenya</option>
                                    <option value="Kiribati" {{ old('country') == 'Kiribati' ? 'selected' : '' }}>Kiribati</option>
                                    <option value="Kuwait" {{ old('country') == 'Kuwait' ? 'selected' : '' }}>Kuwait</option>
                                    <option value="Kyrgyzstan" {{ old('country') == 'Kyrgyzstan' ? 'selected' : '' }}>Kyrgyzstan</option>
                                    <option value="Laos" {{ old('country') == 'Laos' ? 'selected' : '' }}>Laos</option>
                                    <option value="Latvia" {{ old('country') == 'Latvia' ? 'selected' : '' }}>Latvia</option>
                                    <option value="Lebanon" {{ old('country') == 'Lebanon' ? 'selected' : '' }}>Lebanon</option>
                                    <option value="Lesotho" {{ old('country') == 'Lesotho' ? 'selected' : '' }}>Lesotho</option>
                                    <option value="Liberia" {{ old('country') == 'Liberia' ? 'selected' : '' }}>Liberia</option>
                                    <option value="Libya" {{ old('country') == 'Libya' ? 'selected' : '' }}>Libya</option>
                                    <option value="Liechtenstein" {{ old('country') == 'Liechtenstein' ? 'selected' : '' }}>Liechtenstein</option>
                                    <option value="Lithuania" {{ old('country') == 'Lithuania' ? 'selected' : '' }}>Lithuania</option>
                                    <option value="Luxembourg" {{ old('country') == 'Luxembourg' ? 'selected' : '' }}>Luxembourg</option>
                                    <option value="Madagascar" {{ old('country') == 'Madagascar' ? 'selected' : '' }}>Madagascar</option>
                                    <option value="Malawi" {{ old('country') == 'Malawi' ? 'selected' : '' }}>Malawi</option>
                                    <option value="Malaysia" {{ old('country') == 'Malaysia' ? 'selected' : '' }}>Malaysia</option>
                                    <option value="Maldives" {{ old('country') == 'Maldives' ? 'selected' : '' }}>Maldives</option>
                                    <option value="Mali" {{ old('country') == 'Mali' ? 'selected' : '' }}>Mali</option>
                                    <option value="Malta" {{ old('country') == 'Malta' ? 'selected' : '' }}>Malta</option>
                                    <option value="Marshall Islands" {{ old('country') == 'Marshall Islands' ? 'selected' : '' }}>Marshall Islands</option>
                                    <option value="Mauritania" {{ old('country') == 'Mauritania' ? 'selected' : '' }}>Mauritania</option>
                                    <option value="Mauritius" {{ old('country') == 'Mauritius' ? 'selected' : '' }}>Mauritius</option>
                                    <option value="Mexico" {{ old('country') == 'Mexico' ? 'selected' : '' }}>Mexico</option>
                                    <option value="Micronesia" {{ old('country') == 'Micronesia' ? 'selected' : '' }}>Micronesia</option>
                                    <option value="Moldova" {{ old('country') == 'Moldova' ? 'selected' : '' }}>Moldova</option>
                                    <option value="Monaco" {{ old('country') == 'Monaco' ? 'selected' : '' }}>Monaco</option>
                                    <option value="Mongolia" {{ old('country') == 'Mongolia' ? 'selected' : '' }}>Mongolia</option>
                                    <option value="Montenegro" {{ old('country') == 'Montenegro' ? 'selected' : '' }}>Montenegro</option>
                                    <option value="Morocco" {{ old('country') == 'Morocco' ? 'selected' : '' }}>Morocco</option>
                                    <option value="Mozambique" {{ old('country') == 'Mozambique' ? 'selected' : '' }}>Mozambique</option>
                                    <option value="Myanmar" {{ old('country') == 'Myanmar' ? 'selected' : '' }}>Myanmar</option>
                                    <option value="Namibia" {{ old('country') == 'Namibia' ? 'selected' : '' }}>Namibia</option>
                                    <option value="Nauru" {{ old('country') == 'Nauru' ? 'selected' : '' }}>Nauru</option>
                                    <option value="Nepal" {{ old('country') == 'Nepal' ? 'selected' : '' }}>Nepal</option>
                                    <option value="Netherlands" {{ old('country') == 'Netherlands' ? 'selected' : '' }}>Netherlands</option>
                                    <option value="New Zealand" {{ old('country') == 'New Zealand' ? 'selected' : '' }}>New Zealand</option>
                                    <option value="Nicaragua" {{ old('country') == 'Nicaragua' ? 'selected' : '' }}>Nicaragua</option>
                                    <option value="Niger" {{ old('country') == 'Niger' ? 'selected' : '' }}>Niger</option>
                                    <option value="Nigeria" {{ old('country') == 'Nigeria' ? 'selected' : '' }}>Nigeria</option>
                                    <option value="North Korea" {{ old('country') == 'North Korea' ? 'selected' : '' }}>North Korea</option>
                                    <option value="North Macedonia" {{ old('country') == 'North Macedonia' ? 'selected' : '' }}>North Macedonia</option>
                                    <option value="Norway" {{ old('country') == 'Norway' ? 'selected' : '' }}>Norway</option>
                                    <option value="Oman" {{ old('country') == 'Oman' ? 'selected' : '' }}>Oman</option>
                                    <option value="Pakistan" {{ old('country') == 'Pakistan' ? 'selected' : '' }}>Pakistan</option>
                                    <option value="Palau" {{ old('country') == 'Palau' ? 'selected' : '' }}>Palau</option>
                                    <option value="Palestine" {{ old('country') == 'Palestine' ? 'selected' : '' }}>Palestine</option>
                                    <option value="Panama" {{ old('country') == 'Panama' ? 'selected' : '' }}>Panama</option>
                                    <option value="Papua New Guinea" {{ old('country') == 'Papua New Guinea' ? 'selected' : '' }}>Papua New Guinea</option>
                                    <option value="Paraguay" {{ old('country') == 'Paraguay' ? 'selected' : '' }}>Paraguay</option>
                                    <option value="Peru" {{ old('country') == 'Peru' ? 'selected' : '' }}>Peru</option>
                                    <option value="Philippines" {{ old('country') == 'Philippines' ? 'selected' : '' }}>Philippines</option>
                                    <option value="Poland" {{ old('country') == 'Poland' ? 'selected' : '' }}>Poland</option>
                                    <option value="Portugal" {{ old('country') == 'Portugal' ? 'selected' : '' }}>Portugal</option>
                                    <option value="Qatar" {{ old('country') == 'Qatar' ? 'selected' : '' }}>Qatar</option>
                                    <option value="Romania" {{ old('country') == 'Romania' ? 'selected' : '' }}>Romania</option>
                                    <option value="Russia" {{ old('country') == 'Russia' ? 'selected' : '' }}>Russia</option>
                                    <option value="Rwanda" {{ old('country') == 'Rwanda' ? 'selected' : '' }}>Rwanda</option>
                                    <option value="Saint Kitts and Nevis" {{ old('country') == 'Saint Kitts and Nevis' ? 'selected' : '' }}>Saint Kitts and Nevis</option>
                                    <option value="Saint Lucia" {{ old('country') == 'Saint Lucia' ? 'selected' : '' }}>Saint Lucia</option>
                                    <option value="Saint Vincent and the Grenadines" {{ old('country') == 'Saint Vincent and the Grenadines' ? 'selected' : '' }}>Saint Vincent and the Grenadines</option>
                                    <option value="Samoa" {{ old('country') == 'Samoa' ? 'selected' : '' }}>Samoa</option>
                                    <option value="San Marino" {{ old('country') == 'San Marino' ? 'selected' : '' }}>San Marino</option>
                                    <option value="Sao Tome and Principe" {{ old('country') == 'Sao Tome and Principe' ? 'selected' : '' }}>Sao Tome and Principe</option>
                                    <option value="Saudi Arabia" {{ old('country') == 'Saudi Arabia' ? 'selected' : '' }}>Saudi Arabia</option>
                                    <option value="Senegal" {{ old('country') == 'Senegal' ? 'selected' : '' }}>Senegal</option>
                                    <option value="Serbia" {{ old('country') == 'Serbia' ? 'selected' : '' }}>Serbia</option>
                                    <option value="Seychelles" {{ old('country') == 'Seychelles' ? 'selected' : '' }}>Seychelles</option>
                                    <option value="Sierra Leone" {{ old('country') == 'Sierra Leone' ? 'selected' : '' }}>Sierra Leone</option>
                                    <option value="Singapore" {{ old('country') == 'Singapore' ? 'selected' : '' }}>Singapore</option>
                                    <option value="Slovakia" {{ old('country') == 'Slovakia' ? 'selected' : '' }}>Slovakia</option>
                                    <option value="Slovenia" {{ old('country') == 'Slovenia' ? 'selected' : '' }}>Slovenia</option>
                                    <option value="Solomon Islands" {{ old('country') == 'Solomon Islands' ? 'selected' : '' }}>Solomon Islands</option>
                                    <option value="Somalia" {{ old('country') == 'Somalia' ? 'selected' : '' }}>Somalia</option>
                                    <option value="South Africa" {{ old('country') == 'South Africa' ? 'selected' : '' }}>South Africa</option>
                                    <option value="South Korea" {{ old('country') == 'South Korea' ? 'selected' : '' }}>South Korea</option>
                                    <option value="South Sudan" {{ old('country') == 'South Sudan' ? 'selected' : '' }}>South Sudan</option>
                                    <option value="Spain" {{ old('country') == 'Spain' ? 'selected' : '' }}>Spain</option>
                                    <option value="Sri Lanka" {{ old('country') == 'Sri Lanka' ? 'selected' : '' }}>Sri Lanka</option>
                                    <option value="Sudan" {{ old('country') == 'Sudan' ? 'selected' : '' }}>Sudan</option>
                                    <option value="Suriname" {{ old('country') == 'Suriname' ? 'selected' : '' }}>Suriname</option>
                                    <option value="Sweden" {{ old('country') == 'Sweden' ? 'selected' : '' }}>Sweden</option>
                                    <option value="Switzerland" {{ old('country') == 'Switzerland' ? 'selected' : '' }}>Switzerland</option>
                                    <option value="Syria" {{ old('country') == 'Syria' ? 'selected' : '' }}>Syria</option>
                                    <option value="Taiwan" {{ old('country') == 'Taiwan' ? 'selected' : '' }}>Taiwan</option>
                                    <option value="Tajikistan" {{ old('country') == 'Tajikistan' ? 'selected' : '' }}>Tajikistan</option>
                                    <option value="Tanzania" {{ old('country') == 'Tanzania' ? 'selected' : '' }}>Tanzania</option>
                                    <option value="Thailand" {{ old('country') == 'Thailand' ? 'selected' : '' }}>Thailand</option>
                                    <option value="Timor-Leste" {{ old('country') == 'Timor-Leste' ? 'selected' : '' }}>Timor-Leste</option>
                                    <option value="Togo" {{ old('country') == 'Togo' ? 'selected' : '' }}>Togo</option>
                                    <option value="Tonga" {{ old('country') == 'Tonga' ? 'selected' : '' }}>Tonga</option>
                                    <option value="Trinidad and Tobago" {{ old('country') == 'Trinidad and Tobago' ? 'selected' : '' }}>Trinidad and Tobago</option>
                                    <option value="Tunisia" {{ old('country') == 'Tunisia' ? 'selected' : '' }}>Tunisia</option>
                                    <option value="Turkey" {{ old('country') == 'Turkey' ? 'selected' : '' }}>Turkey</option>
                                    <option value="Turkmenistan" {{ old('country') == 'Turkmenistan' ? 'selected' : '' }}>Turkmenistan</option>
                                    <option value="Tuvalu" {{ old('country') == 'Tuvalu' ? 'selected' : '' }}>Tuvalu</option>
                                    <option value="Uganda" {{ old('country') == 'Uganda' ? 'selected' : '' }}>Uganda</option>
                                    <option value="Ukraine" {{ old('country') == 'Ukraine' ? 'selected' : '' }}>Ukraine</option>
                                    <option value="United Arab Emirates" {{ old('country') == 'United Arab Emirates' ? 'selected' : '' }}>United Arab Emirates</option>
                                    <option value="United Kingdom" {{ old('country') == 'United Kingdom' ? 'selected' : '' }}>United Kingdom</option>
                                    <option value="United States" {{ old('country') == 'United States' ? 'selected' : '' }}>United States</option>
                                    <option value="Uruguay" {{ old('country') == 'Uruguay' ? 'selected' : '' }}>Uruguay</option>
                                    <option value="Uzbekistan" {{ old('country') == 'Uzbekistan' ? 'selected' : '' }}>Uzbekistan</option>
                                    <option value="Vanuatu" {{ old('country') == 'Vanuatu' ? 'selected' : '' }}>Vanuatu</option>
                                    <option value="Vatican City" {{ old('country') == 'Vatican City' ? 'selected' : '' }}>Vatican City</option>
                                    <option value="Venezuela" {{ old('country') == 'Venezuela' ? 'selected' : '' }}>Venezuela</option>
                                    <option value="Vietnam" {{ old('country') == 'Vietnam' ? 'selected' : '' }}>Vietnam</option>
                                    <option value="Yemen" {{ old('country') == 'Yemen' ? 'selected' : '' }}>Yemen</option>
                                    <option value="Zambia" {{ old('country') == 'Zambia' ? 'selected' : '' }}>Zambia</option>
                                    <option value="Zimbabwe" {{ old('country') == 'Zimbabwe' ? 'selected' : '' }}>Zimbabwe</option>
                                </select>
                                @error('country')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Property Size --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="mdi mdi-floor-plan text-info"></i> Property Size
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="square_footage_interior">Interior Square Footage</label>
                                <input type="number" step="0.01" class="form-control @error('square_footage_interior') is-invalid @enderror" 
                                    id="square_footage_interior" name="square_footage_interior" 
                                    value="{{ old('square_footage_interior') }}" placeholder="e.g., 2500.00">
                                @error('square_footage_interior')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="square_footage_green">Green Space Square Footage</label>
                                <input type="number" step="0.01" class="form-control @error('square_footage_green') is-invalid @enderror" 
                                    id="square_footage_green" name="square_footage_green" 
                                    value="{{ old('square_footage_green') }}" placeholder="e.g., 1000.00">
                                @error('square_footage_green')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="square_footage_paved">Paved Area Square Footage</label>
                                <input type="number" step="0.01" class="form-control @error('square_footage_paved') is-invalid @enderror" 
                                    id="square_footage_paved" name="square_footage_paved" 
                                    value="{{ old('square_footage_paved') }}" placeholder="e.g., 500.00">
                                @error('square_footage_paved')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="square_footage_extra">Extra Space Square Footage</label>
                                <input type="number" step="0.01" class="form-control @error('square_footage_extra') is-invalid @enderror" 
                                    id="square_footage_extra" name="square_footage_extra" 
                                    value="{{ old('square_footage_extra') }}" placeholder="e.g., 200.00">
                                @error('square_footage_extra')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Owner Information --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="mdi mdi-account text-warning"></i> Owner Information
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="owner_first_name">Owner First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('owner_first_name') is-invalid @enderror" 
                                    id="owner_first_name" name="owner_first_name" value="{{ old('owner_first_name') }}" required>
                                @error('owner_first_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="owner_phone">Owner Phone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control @error('owner_phone') is-invalid @enderror" 
                                    id="owner_phone" name="owner_phone" value="{{ old('owner_phone') }}" required>
                                @error('owner_phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="owner_email">Owner Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('owner_email') is-invalid @enderror" 
                                    id="owner_email" name="owner_email" value="{{ old('owner_email') }}" required>
                                @error('owner_email')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Property Admin (Optional) --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="mdi mdi-account-tie text-secondary"></i> Property Administrator <small class="text-muted">(Optional)</small>
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="admin_first_name">Admin First Name</label>
                                <input type="text" class="form-control @error('admin_first_name') is-invalid @enderror" 
                                    id="admin_first_name" name="admin_first_name" value="{{ old('admin_first_name') }}">
                                @error('admin_first_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="admin_last_name">Admin Last Name</label>
                                <input type="text" class="form-control @error('admin_last_name') is-invalid @enderror" 
                                    id="admin_last_name" name="admin_last_name" value="{{ old('admin_last_name') }}">
                                @error('admin_last_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="admin_email">Admin Email</label>
                                <input type="email" class="form-control @error('admin_email') is-invalid @enderror" 
                                    id="admin_email" name="admin_email" value="{{ old('admin_email') }}">
                                @error('admin_email')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="admin_phone">Admin Phone</label>
                                <input type="tel" class="form-control @error('admin_phone') is-invalid @enderror" 
                                    id="admin_phone" name="admin_phone" value="{{ old('admin_phone') }}">
                                @error('admin_phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Occupancy Information --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="mdi mdi-account-group text-purple"></i> Occupancy Information
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="occupied_by">Occupied By</label>
                                <select class="form-control @error('occupied_by') is-invalid @enderror" id="occupied_by" name="occupied_by">
                                    <option value="">Select Option</option>
                                    <option value="owner" {{ old('occupied_by') == 'owner' ? 'selected' : '' }}>Owner</option>
                                    <option value="family" {{ old('occupied_by') == 'family' ? 'selected' : '' }}>Family</option>
                                    <option value="tenants" {{ old('occupied_by') == 'tenants' ? 'selected' : '' }}>Tenant(s)</option>
                                    <option value="mixed" {{ old('occupied_by') == 'mixed' ? 'selected' : '' }}>Mixed (Owner + Tenants)</option>
                                </select>
                                @error('occupied_by')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check mt-3">
                                <input type="checkbox" class="form-check-input" id="has_pets" name="has_pets" value="1" 
                                    {{ old('has_pets') ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_pets">
                                    Has Pets
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check mt-3">
                                <input type="checkbox" class="form-check-input" id="has_kids" name="has_kids" value="1" 
                                    {{ old('has_kids') ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_kids">
                                    Has Children
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12 mt-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="has_tenants" name="has_tenants" value="1" 
                                    {{ old('has_tenants') ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_tenants">
                                    This property has multiple tenant units
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6 mt-3" id="number_of_units_wrapper" style="display: none;">
                            <div class="form-group">
                                <label for="number_of_units">Number of Tenant Units</label>
                                <input type="number" class="form-control @error('number_of_units') is-invalid @enderror" 
                                    id="number_of_units" name="number_of_units" value="{{ old('number_of_units') }}" min="1">
                                @error('number_of_units')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Property Details --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="mdi mdi-text-box text-cyan"></i> Property Details
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="personality">Property Personality/Style</label>
                                <select class="form-control @error('personality') is-invalid @enderror" 
                                    id="personality" name="personality">
                                    <option value="">Select Property Personality</option>
                                    <option value="calm" {{ old('personality') == 'calm' ? 'selected' : '' }}>Calm & Peaceful</option>
                                    <option value="busy" {{ old('personality') == 'busy' ? 'selected' : '' }}>Busy & Active</option>
                                    <option value="luxury" {{ old('personality') == 'luxury' ? 'selected' : '' }}>Luxury & Upscale</option>
                                    <option value="high-use" {{ old('personality') == 'high-use' ? 'selected' : '' }}>High-Use & Heavy Traffic</option>
                                </select>
                                @error('personality')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="known_problems">Known Problems/Issues</label>
                                <textarea class="form-control @error('known_problems') is-invalid @enderror" 
                                    id="known_problems" name="known_problems" rows="3" 
                                    placeholder="List any known problems, issues, or areas requiring attention...">{{ old('known_problems') }}</textarea>
                                @error('known_problems')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="sensitivities">Sensitivities or Special Considerations</label>
                                <textarea class="form-control @error('sensitivities') is-invalid @enderror" 
                                    id="sensitivities" name="sensitivities" rows="3" 
                                    placeholder="Any allergies, chemical sensitivities, or special handling requirements...">{{ old('sensitivities') }}</textarea>
                                <small class="form-text text-muted">This will be stored as an array. Separate items with commas.</small>
                                @error('sensitivities')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Property Photos --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="mdi mdi-camera text-danger"></i> Property Photos
                        <span class="badge badge-info ms-2">Up to 15 photos</span>
                    </h4>
                    
                    <div class="form-group">
                        <label for="property_photos">Upload Property Photos <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('property_photos') is-invalid @enderror" 
                            id="property_photos" name="property_photos[]" multiple accept="image/jpeg,image/png,image/jpg,image/gif">
                        <small class="form-text text-muted">
                            <i class="mdi mdi-information"></i> You can select 10-15 photos at once (max 10MB each). 
                            Supported formats: JPG, PNG, GIF
                        </small>
                        @error('property_photos')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div id="photo-count" class="alert alert-info mt-2" style="display: none;">
                        <i class="mdi mdi-image-multiple"></i> <strong><span id="photo-count-number">0</span></strong> photo(s) selected
                    </div>

                    <div id="photo-preview" class="row mt-3"></div>
                </div>
            </div>

            {{-- Blueprint/Floor Plan --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="mdi mdi-floor-plan text-primary"></i> Blueprint / Floor Plan
                        <span class="badge badge-secondary ms-2">Optional</span>
                    </h4>
                    
                    <div class="form-group">
                        <label for="blueprint_file">Upload Blueprint or Floor Plan</label>
                        <input type="file" class="form-control @error('blueprint_file') is-invalid @enderror" 
                            id="blueprint_file" name="blueprint_file" accept="image/jpeg,image/png,image/jpg,application/pdf">
                        <small class="form-text text-muted">
                            <i class="mdi mdi-information"></i> Upload property blueprint or floor plan (max 20MB). 
                            Supported formats: PDF, JPG, PNG
                        </small>
                        @error('blueprint_file')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div id="blueprint-preview" class="mt-3"></div>
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('client.properties.index') }}" class="btn btn-light">
                            <i class="mdi mdi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-check"></i> Submit Property for Approval
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.photo-preview-item {
    position: relative;
    margin-bottom: 15px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.photo-preview-item:hover {
    transform: scale(1.02);
}
.photo-preview-item img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border: 2px solid #e0e0e0;
}
.photo-preview-item .photo-preview-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
    padding: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.photo-preview-item .remove-photo-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.photo-preview-item .remove-photo-btn:hover {
    background: rgba(220, 53, 69, 1);
    transform: scale(1.1);
}
.blueprint-preview-item {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    background: #f8f9fa;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-update property code preview based on brand
    const brandInput = document.getElementById('property_brand');
    const codePreview = document.getElementById('property_code_preview');
    
    function updatePropertyCodePreview() {
        const brand = brandInput.value.trim();
        const prefix = brand ? brand.substring(0, 3).toUpperCase() : 'PROP';
        const timestamp = Math.floor(Date.now() / 1000); // Current timestamp
        codePreview.value = prefix + '-' + timestamp;
    }
    
    // Update on brand input change
    brandInput.addEventListener('input', updatePropertyCodePreview);
    
    // Initialize with current timestamp
    updatePropertyCodePreview();
    
    // Toggle tenant units field
    const hasTenants = document.getElementById('has_tenants');
    const numberOfUnitsWrapper = document.getElementById('number_of_units_wrapper');
    
    hasTenants.addEventListener('change', function() {
        numberOfUnitsWrapper.style.display = this.checked ? 'block' : 'none';
    });

    // Photo preview with validation and accumulation
    const photoInput = document.getElementById('property_photos');
    const photoPreview = document.getElementById('photo-preview');
    const photoCount = document.getElementById('photo-count');
    const photoCountNumber = document.getElementById('photo-count-number');
    let selectedFiles = []; // Store accumulated files
    
    photoInput.addEventListener('change', function(e) {
        const newFiles = Array.from(e.target.files);
        
        // Add new files to accumulated list
        newFiles.forEach(file => {
            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert(`${file.name} exceeds 10MB. Please choose a smaller file.`);
                return;
            }
            
            // Check if file already exists (by name and size)
            const exists = selectedFiles.some(f => f.name === file.name && f.size === file.size);
            if (!exists) {
                selectedFiles.push(file);
            }
        });
        
        // Validate total file count
        if (selectedFiles.length > 15) {
            alert(`Maximum 15 photos allowed. You have selected ${selectedFiles.length} photos. Please remove some.`);
            selectedFiles = selectedFiles.slice(0, 15); // Keep only first 15
        }
        
        // Update display
        updatePhotoDisplay();
    });
    
    function updatePhotoDisplay() {
        photoPreview.innerHTML = '';
        
        if (selectedFiles.length === 0) {
            photoCount.style.display = 'none';
            return;
        }
        
        // Show photo count
        photoCountNumber.textContent = selectedFiles.length;
        photoCount.style.display = 'block';
        
        // Create a DataTransfer object to update the file input
        const dataTransfer = new DataTransfer();
        
        selectedFiles.forEach((file, index) => {
            // Add to DataTransfer
            dataTransfer.items.add(file);
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-md-3 mb-3';
                    col.innerHTML = `
                        <div class="photo-preview-item">
                            <img src="${e.target.result}" alt="Photo ${index + 1}">
                            <div class="photo-preview-overlay">
                                <span class="badge badge-light">Photo ${index + 1}</span>
                                <span class="badge badge-info">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                            </div>
                            <button type="button" class="remove-photo-btn" data-index="${index}" title="Remove photo">
                                <i class="mdi mdi-close"></i>
                            </button>
                        </div>
                    `;
                    photoPreview.appendChild(col);
                    
                    // Add click handler for remove button
                    col.querySelector('.remove-photo-btn').addEventListener('click', function() {
                        removePhoto(index);
                    });
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Update the file input with accumulated files
        photoInput.files = dataTransfer.files;
    }
    
    function removePhoto(index) {
        selectedFiles.splice(index, 1);
        updatePhotoDisplay();
    }

    // Make country dropdown searchable
    const countrySelect = document.getElementById('country');
    const oldCountry = "{{ old('country') }}";
    
    // Add search functionality
    countrySelect.addEventListener('keyup', function(e) {
        const searchTerm = this.value.toLowerCase();
        const options = Array.from(this.options);
        
        options.forEach(option => {
            if (option.value === '') return; // Keep the "Select Country" option
            
            const text = option.text.toLowerCase();
            if (text.includes(searchTerm)) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
    });
    
    // Set selected country if old value exists
    if (oldCountry) {
        countrySelect.value = oldCountry;
    }

    // Blueprint preview with validation
    const blueprintInput = document.getElementById('blueprint_file');
    const blueprintPreview = document.getElementById('blueprint-preview');
    
    blueprintInput.addEventListener('change', function(e) {
        blueprintPreview.innerHTML = '';
        const file = e.target.files[0];
        
        if (file) {
            // Validate file size (20MB)
            if (file.size > 20 * 1024 * 1024) {
                alert('Blueprint file exceeds 20MB. Please choose a smaller file.');
                this.value = '';
                return;
            }
            
            if (file.type === 'application/pdf') {
                blueprintPreview.innerHTML = `
                    <div class="blueprint-preview-item">
                        <div class="d-flex align-items-center p-3">
                            <i class="mdi mdi-file-pdf" style="font-size: 3rem; color: #d32f2f;"></i>
                            <div class="ms-3">
                                <p class="mb-0"><strong>${file.name}</strong></p>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                                <span class="badge badge-success ms-2">PDF</span>
                            </div>
                        </div>
                    </div>
                `;
            } else if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    blueprintPreview.innerHTML = `
                        <div class="blueprint-preview-item">
                            <img src="${e.target.result}" style="max-width: 100%; height: auto; border-radius: 8px;" alt="Blueprint">
                            <div class="mt-2">
                                <span class="badge badge-info">${file.name}</span>
                                <span class="badge badge-secondary ms-2">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                            </div>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        }
    });

});
</script>

<style>
/* Fix for dark input fields - make them light and readable */
.form-control,
.form-control:focus,
.form-select,
select.form-control,
textarea.form-control {
    background-color: #ffffff !important;
    color: #000000 !important;
    border: 1px solid #ced4da !important;
}

.form-control::placeholder {
    color: #6c757d !important;
    opacity: 0.7;
}

/* Fix for select dropdowns */
.form-control option {
    background-color: #ffffff !important;
    color: #000000 !important;
}

/* Fix for disabled/readonly fields */
.form-control:disabled,
.form-control[readonly] {
    background-color: #e9ecef !important;
    color: #6c757d !important;
}

/* Fix for labels */
.form-group label {
    color: #495057 !important;
}

/* Fix for small text */
.form-text,
small.form-text {
    color: #6c757d !important;
}

/* Ensure card text is visible */
.card-body {
    color: #212529 !important;
}

.card-title {
    color: #212529 !important;
}
</style>
@endsection
