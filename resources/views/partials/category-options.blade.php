@php($selectedCategory = old('category', $prefillCategory ?? ''))
<option value="Symposium" {{ $selectedCategory === 'Symposium' ? 'selected' : '' }}>Symposium</option>
<option value="Convocation" {{ $selectedCategory === 'Convocation' ? 'selected' : '' }}>Convocation</option>
<option value="Religious Activity" {{ $selectedCategory === 'Religious Activity' ? 'selected' : '' }}>Religious Activity</option>
<option value="Socio-Cultural and Sports" {{ $selectedCategory === 'Socio-Cultural and Sports' ? 'selected' : '' }}>Socio-Cultural and Sports</option>
<option value="Makakalikasan (Clean and Green)" {{ $selectedCategory === 'Makakalikasan (Clean and Green)' ? 'selected' : '' }}>Makakalikasan (Clean and Green)</option>
<option value="Extension Services Conducted" {{ $selectedCategory === 'Extension Services Conducted' ? 'selected' : '' }}>Extension Services Conducted</option>
