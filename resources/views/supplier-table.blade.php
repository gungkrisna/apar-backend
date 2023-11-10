<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
</head>

<body>
    <table>
        <thead>
            <tr>
                <th>
                    <div class="flex flex-col text-center mx-6 my-8">
                        <h1 class="">
                            {{ config('app.name') }}
                        </h1>
                        <p>Data {{ $model }}</p>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <!-- Existing table goes here -->
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-blue-500">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telepon</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($suppliers as $supplier)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $supplier->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $supplier->phone }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $supplier->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $supplier->address }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>