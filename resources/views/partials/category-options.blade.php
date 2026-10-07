@php($selectedCategory = old('category', $prefillCategory ?? ''))
@php($categories = $gpoaCategories ?? ['Symposium', 'Convocation', 'Religious Activity', 'Socio-Cultural and Sports', 'Makakalikasan (Clean and Green)', 'Extension Services Conducted'])
@foreach($categories as $category)
	<option value="{{ $category }}" {{ $selectedCategory === $category ? 'selected' : '' }}>{{ $category }}</option>
@endforeach
