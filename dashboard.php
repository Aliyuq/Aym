<?php
/**
 * ALMAF BAKERY - Interactive Dashboard
 * Beautiful, modern Tailwind CSS UI with real-time JavaScript calculations
 * Features: Production tracking, Sales logging, Payroll management, RBAC
 */

require_once 'auth.php';
require_once 'db.php';

requireAuth(); // Ensure user is logged in

$currentUser = getCurrentUser();
$currentRole = getCurrentRole();
$isAdmin = isAdmin();

// Fetch dashboard metrics
$todayProduction = null;
$totalRevenue = 0;
$staffCount = 0;
$lowStockItems = [];

try {
    // Today's production
    $prodQuery = "SELECT * FROM daily_production WHERE date = CURDATE()";
    $prodStmt = executeQuery($pdo, $prodQuery);
    $todayProduction = $prodStmt->fetch();
    
    // Today's total revenue
    $revQuery = "SELECT SUM(total_revenue) as total FROM sales_logs WHERE date = CURDATE()";
    $revStmt = executeQuery($pdo, $revQuery);
    $revResult = $revStmt->fetch();
    $totalRevenue = $revResult['total'] ?? 0;
    
    // Staff count
    $staffCountQuery = "SELECT COUNT(*) as count FROM staff WHERE is_active = TRUE";
    $staffCountStmt = executeQuery($pdo, $staffCountQuery);
    $staffCountResult = $staffCountStmt->fetch();
    $staffCount = $staffCountResult['count'];
    
    // Low stock alerts
    $lowStockQuery = "SELECT id, name, current_stock, minimum_stock_threshold, unit FROM ingredients WHERE current_stock <= minimum_stock_threshold ORDER BY current_stock ASC";
    $lowStockStmt = executeQuery($pdo, $lowStockQuery);
    $lowStockItems = $lowStockStmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard query error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALMAF BAKERY - Management Dashboard</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-active {
            @apply bg-amber-100 border-r-4 border-amber-600;
        }
        .btn-primary {
            @apply px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-all duration-200 shadow-md;
        }
        .btn-secondary {
            @apply px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300 transition-all duration-200;
        }
        .card {
            @apply bg-white rounded-xl shadow-md p-6 border border-slate-100 transition-all duration-200 hover:shadow-lg;
        }
        .badge-success {
            @apply inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800;
        }
        .badge-warning {
            @apply inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800;
        }
        .badge-danger {
            @apply inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-rose-100 text-rose-800;
        }
        .input-field {
            @apply w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-200;
        }
        .table-header {
            @apply bg-slate-50 px-6 py-4 text-left text-sm font-semibold text-slate-900 border-b border-slate-200;
        }
        .table-cell {
            @apply px-6 py-4 border-b border-slate-200 text-sm text-slate-700;
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <aside class="w-64 bg-white border-r border-slate-200 shadow-lg">
            <div class="p-6 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-amber-600 rounded-lg flex items-center justify-center text-white font-bold">AB</div>
                    <div>
                        <h1 class="text-xl font-bold text-amber-900">ALMAF BAKERY</h1>
                        <p class="text-xs text-slate-500">Management System</p>
                    </div>
                </div>
            </div>
            
            <!-- User Info -->
            <div class="p-4 bg-amber-50 border-b border-slate-200">
                <p class="text-sm font-semibold text-amber-900">Welcome, <?php echo htmlspecialchars($currentUser['name']); ?></p>
                <p class="text-xs text-slate-600 mt-1">Role: <span class="font-medium"><?php echo ucfirst($currentRole); ?></span></p>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="p-4 space-y-2">
                <a href="#dashboard" onclick="showSection('dashboard')" class="sidebar-active block px-4 py-3 rounded-lg text-amber-900 font-medium">
                    📊 Dashboard
                </a>
                <a href="#production" onclick="showSection('production')" class="block px-4 py-3 rounded-lg text-slate-700 hover:bg-slate-100 transition-all duration-200">
                    🍞 Daily Production
                </a>
                <a href="#sales" onclick="showSection('sales')" class="block px-4 py-3 rounded-lg text-slate-700 hover:bg-slate-100 transition-all duration-200">
                    💰 Sales Log
                </a>
                <a href="#inventory" onclick="showSection('inventory')" class="block px-4 py-3 rounded-lg text-slate-700 hover:bg-slate-100 transition-all duration-200">
                    📦 Inventory
                </a>
                
                <?php if (hasRole(['Admin', 'Manager'])): ?>
                <a href="#payroll" onclick="showSection('payroll')" class="block px-4 py-3 rounded-lg text-slate-700 hover:bg-slate-100 transition-all duration-200">
                    💳 Payroll
                </a>
                <?php endif; ?>
                
                <?php if (isAdmin()): ?>
                <a href="#products" onclick="showSection('products')" class="block px-4 py-3 rounded-lg text-slate-700 hover:bg-slate-100 transition-all duration-200">
                    🏷️ Products & Pricing
                </a>
                <a href="#staff" onclick="showSection('staff')" class="block px-4 py-3 rounded-lg text-slate-700 hover:bg-slate-100 transition-all duration-200">
                    👥 Staff Management
                </a>
                <a href="#distributors" onclick="showSection('distributors')" class="block px-4 py-3 rounded-lg text-slate-700 hover:bg-slate-100 transition-all duration-200">
                    🚚 Distributors
                </a>
                <?php endif; ?>
            </nav>
            
            <!-- Logout -->
            <div class="absolute bottom-4 left-4 right-4">
                <a href="logout.php" class="btn-secondary w-full text-center">
                    🚪 Logout
                </a>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto">
            <!-- Top Header -->
            <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-10">
                <div class="px-8 py-4 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-amber-900">Dashboard</h2>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-slate-600">📅 <span id="current-date"></span></span>
                        <div class="w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center font-bold cursor-pointer hover:bg-amber-700">
                            <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <div class="p-8">
                <!-- Dashboard Overview Section -->
                <div id="dashboard" class="section fade-in">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">📊 Today's Overview</h2>
                    
                    <!-- Key Metrics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <!-- Today's Production -->
                        <div class="card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-slate-600 text-sm font-medium">Production Yield</p>
                                    <p class="text-3xl font-bold text-amber-600 mt-2">
                                        <?php echo $todayProduction['net_yield'] ?? 0; ?>
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">Sellable loaves</p>
                                </div>
                                <div class="text-4xl">🍞</div>
                            </div>
                        </div>
                        
                        <!-- Today's Revenue -->
                        <div class="card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-slate-600 text-sm font-medium">Today's Revenue</p>
                                    <p class="text-3xl font-bold text-emerald-600 mt-2">
                                        ₦<?php echo number_format($totalRevenue, 2); ?>
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">Total sales</p>
                                </div>
                                <div class="text-4xl">💰</div>
                            </div>
                        </div>
                        
                        <!-- Active Staff -->
                        <div class="card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-slate-600 text-sm font-medium">Active Staff</p>
                                    <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $staffCount; ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Team members</p>
                                </div>
                                <div class="text-4xl">👥</div>
                            </div>
                        </div>
                        
                        <!-- Low Stock Alerts -->
                        <div class="card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-slate-600 text-sm font-medium">Stock Alerts</p>
                                    <p class="text-3xl font-bold text-rose-600 mt-2"><?php echo count($lowStockItems); ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Items below threshold</p>
                                </div>
                                <div class="text-4xl">⚠️</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Low Stock Items Alert -->
                    <?php if (count($lowStockItems) > 0): ?>
                    <div class="bg-rose-50 border-l-4 border-rose-500 p-6 rounded-lg mb-8">
                        <h3 class="text-lg font-semibold text-rose-900 mb-4">⚠️ Low Stock Alerts</h3>
                        <div class="space-y-3">
                            <?php foreach ($lowStockItems as $item): ?>
                            <div class="flex items-center justify-between bg-white p-3 rounded">
                                <div>
                                    <p class="font-medium text-slate-900"><?php echo htmlspecialchars($item['name']); ?></p>
                                    <p class="text-sm text-slate-600">Current: <?php echo $item['current_stock']; ?> <?php echo $item['unit']; ?> (Threshold: <?php echo $item['minimum_stock_threshold']; ?>)</p>
                                </div>
                                <span class="badge-danger">Low Stock</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Daily Production Section -->
                <div id="production" class="section hidden fade-in">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">🍞 Daily Production Tracker</h2>
                    
                    <div class="card">
                        <form id="productionForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Flour Bags Used -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Flour Bags Used</label>
                                    <input type="number" id="flourBags" class="input-field" placeholder="Enter number of bags" min="0" value="<?php echo $todayProduction['flour_bags_used'] ?? 0; ?>">
                                    <p class="text-xs text-slate-500 mt-1">📌 1 bag = 100 loaves (standard)</p>
                                </div>
                                
                                <!-- Expected Yield (Auto-calculated) -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Expected Yield</label>
                                    <input type="number" id="expectedYield" class="input-field bg-slate-100" placeholder="Auto-calculated" readonly>
                                    <p class="text-xs text-slate-500 mt-1">Formula: Bags × 100</p>
                                </div>
                                
                                <!-- Actual Yield -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Actual Yield</label>
                                    <input type="number" id="actualYield" class="input-field" placeholder="Enter actual loaves" min="0" value="<?php echo $todayProduction['actual_yield'] ?? 0; ?>">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Damaged Loaves -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Damaged Loaves</label>
                                    <input type="number" id="damagedLoaves" class="input-field" placeholder="Enter damaged count" min="0" value="<?php echo $todayProduction['damaged_loaves'] ?? 0; ?>">
                                </div>
                                
                                <!-- Net Yield (Auto-calculated) -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Net Sellable Yield</label>
                                    <input type="number" id="netYield" class="input-field bg-emerald-50 border-emerald-300" placeholder="Auto-calculated" readonly>
                                    <p class="text-xs text-slate-500 mt-1">Formula: Actual - Damaged</p>
                                </div>
                                
                                <!-- Wastage Percentage -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Wastage %</label>
                                    <input type="text" id="wastagePercent" class="input-field bg-rose-50 border-rose-300" placeholder="Auto-calculated" readonly>
                                </div>
                            </div>
                            
                            <div class="flex gap-4">
                                <button type="submit" class="btn-primary">💾 Save Production Record</button>
                                <button type="reset" class="btn-secondary">↻ Clear Form</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Sales Log Section -->
                <div id="sales" class="section hidden fade-in">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">💰 Daily Sales Log</h2>
                    
                    <div class="card">
                        <form id="salesForm" class="space-y-6 mb-8">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Product Selection -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Product</label>
                                    <select id="productSelect" class="input-field">
                                        <option value="">-- Select Product --</option>
                                        <option value="PROD_WL001" data-wholesale="2.50" data-retail="4.00">White Loaf</option>
                                        <option value="PROD_BL001" data-wholesale="3.00" data-retail="5.00">Brown Bread</option>
                                        <option value="PROD_WHL001" data-wholesale="3.50" data-retail="5.50">Whole Wheat</option>
                                    </select>
                                </div>
                                
                                <!-- Quantity Sold -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Quantity Sold</label>
                                    <input type="number" id="quantitySold" class="input-field" placeholder="Enter quantity" min="0">
                                </div>
                                
                                <!-- Sale Type -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Sale Type</label>
                                    <select id="saleType" class="input-field">
                                        <option value="Retail">🏪 Retail</option>
                                        <option value="Wholesale">📦 Wholesale</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Unit Price (Auto-fetched) -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Unit Price</label>
                                    <input type="text" id="unitPrice" class="input-field bg-slate-100" placeholder="Auto-calculated" readonly>
                                    <p class="text-xs text-slate-500 mt-1">Based on sale type</p>
                                </div>
                                
                                <!-- Total Revenue (Auto-calculated) -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Total Revenue</label>
                                    <input type="text" id="totalSaleRevenue" class="input-field bg-emerald-50 border-emerald-300 text-emerald-700 font-bold" placeholder="Auto-calculated" readonly>
                                </div>
                                
                                <!-- Timestamp -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Date</label>
                                    <input type="date" id="saleDate" class="input-field" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            
                            <div class="flex gap-4">
                                <button type="submit" class="btn-primary">💾 Log Sale</button>
                                <button type="reset" class="btn-secondary">↻ Clear</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Inventory Section -->
                <div id="inventory" class="section hidden fade-in">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">📦 Ingredient Inventory</h2>
                    
                    <div class="card overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="table-header">Ingredient</th>
                                    <th class="table-header">Current Stock</th>
                                    <th class="table-header">Min. Threshold</th>
                                    <th class="table-header">Unit</th>
                                    <th class="table-header">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="table-cell">All-Purpose Flour</td>
                                    <td class="table-cell">150.00</td>
                                    <td class="table-cell">50.00</td>
                                    <td class="table-cell">kg</td>
                                    <td class="table-cell"><span class="badge-success">✓ OK</span></td>
                                </tr>
                                <tr>
                                    <td class="table-cell">Salt</td>
                                    <td class="table-cell">10.00</td>
                                    <td class="table-cell">5.00</td>
                                    <td class="table-cell">kg</td>
                                    <td class="table-cell"><span class="badge-warning">⚠ Low</span></td>
                                </tr>
                                <tr>
                                    <td class="table-cell">Yeast</td>
                                    <td class="table-cell">5.00</td>
                                    <td class="table-cell">2.00</td>
                                    <td class="table-cell">kg</td>
                                    <td class="table-cell"><span class="badge-success">✓ OK</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Payroll Section (Admin/Manager only) -->
                <?php if (hasRole(['Admin', 'Manager'])): ?>
                <div id="payroll" class="section hidden fade-in">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">💳 Payroll Management</h2>
                    
                    <div class="card">
                        <form id="payrollForm" class="space-y-6 mb-8">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Staff Selection -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Staff Member</label>
                                    <select id="staffSelect" class="input-field">
                                        <option value="">-- Select Staff --</option>
                                        <option value="STF_ADM001" data-rate="500">Aliyu Manager</option>
                                    </select>
                                </div>
                                
                                <!-- Days Worked -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Days Worked</label>
                                    <input type="number" id="daysWorked" class="input-field" placeholder="e.g., 20" step="0.5" min="0" max="31" value="1">
                                </div>
                                
                                <!-- Daily Rate (Auto-fetched) -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Daily Rate</label>
                                    <input type="text" id="dailyRate" class="input-field bg-slate-100" placeholder="Auto-fetched" readonly>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Total Pay (Auto-calculated) -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Total Pay</label>
                                    <input type="text" id="totalPay" class="input-field bg-emerald-50 border-emerald-300 text-emerald-700 font-bold" placeholder="Auto-calculated" readonly>
                                    <p class="text-xs text-slate-500 mt-1">Formula: Days × Daily Rate</p>
                                </div>
                                
                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                                    <select id="paymentStatus" class="input-field">
                                        <option value="Unpaid">❌ Unpaid</option>
                                        <option value="Paid">✓ Paid</option>
                                        <option value="Pending">⏳ Pending</option>
                                    </select>
                                </div>
                                
                                <!-- Period -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Payment Date</label>
                                    <input type="date" id="paymentDate" class="input-field" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            
                            <div class="flex gap-4">
                                <button type="submit" class="btn-primary">💾 Record Payment</button>
                                <button type="reset" class="btn-secondary">↻ Clear</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Products & Pricing Section (Admin only) -->
                <?php if (isAdmin()): ?>
                <div id="products" class="section hidden fade-in">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">🏷️ Products & Pricing</h2>
                    
                    <div class="card overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="table-header">Product Name</th>
                                    <th class="table-header">Wholesale Price</th>
                                    <th class="table-header">Retail Price</th>
                                    <th class="table-header">Margin</th>
                                    <th class="table-header">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="table-cell">White Loaf</td>
                                    <td class="table-cell">₦2.50</td>
                                    <td class="table-cell">₦4.00</td>
                                    <td class="table-cell"><span class="badge-success">+60%</span></td>
                                    <td class="table-cell"><button class="btn-secondary text-xs">✏️ Edit</button></td>
                                </tr>
                                <tr>
                                    <td class="table-cell">Brown Bread</td>
                                    <td class="table-cell">₦3.00</td>
                                    <td class="table-cell">₦5.00</td>
                                    <td class="table-cell"><span class="badge-success">+67%</span></td>
                                    <td class="table-cell"><button class="btn-secondary text-xs">✏️ Edit</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- JavaScript: Real-time Calculations & Interactivity -->
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            updateCurrentDate();
            initEventListeners();
        });
        
        // Update current date
        function updateCurrentDate() {
            const dateElement = document.getElementById('current-date');
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            dateElement.textContent = new Date().toLocaleDateString('en-US', options);
        }
        
        // Initialize event listeners for real-time calculations
        function initEventListeners() {
            // Production form calculations
            const flourBags = document.getElementById('flourBags');
            const expectedYield = document.getElementById('expectedYield');
            const actualYield = document.getElementById('actualYield');
            const damagedLoaves = document.getElementById('damagedLoaves');
            const netYield = document.getElementById('netYield');
            const wastagePercent = document.getElementById('wastagePercent');
            
            if (flourBags) {
                flourBags.addEventListener('input', calculateProduction);
            }
            if (actualYield) {
                actualYield.addEventListener('input', calculateProduction);
            }
            if (damagedLoaves) {
                damagedLoaves.addEventListener('input', calculateProduction);
            }
            
            // Sales form calculations
            const productSelect = document.getElementById('productSelect');
            const quantitySold = document.getElementById('quantitySold');
            const saleType = document.getElementById('saleType');
            
            if (productSelect) {
                productSelect.addEventListener('change', calculateSales);
            }
            if (saleType) {
                saleType.addEventListener('change', calculateSales);
            }
            if (quantitySold) {
                quantitySold.addEventListener('input', calculateSales);
            }
            
            // Payroll calculations
            const staffSelect = document.getElementById('staffSelect');
            const daysWorked = document.getElementById('daysWorked');
            
            if (staffSelect) {
                staffSelect.addEventListener('change', calculatePayroll);
            }
            if (daysWorked) {
                daysWorked.addEventListener('input', calculatePayroll);
            }
        }
        
        // Calculate daily production
        function calculateProduction() {
            const flourBags = parseFloat(document.getElementById('flourBags').value) || 0;
            const actualYield = parseFloat(document.getElementById('actualYield').value) || 0;
            const damagedLoaves = parseFloat(document.getElementById('damagedLoaves').value) || 0;
            
            const expectedYield = flourBags * 100; // 1 bag = 100 loaves
            const netYield = actualYield - damagedLoaves;
            const wastagePercent = actualYield > 0 ? ((damagedLoaves / actualYield) * 100).toFixed(2) : 0;
            
            document.getElementById('expectedYield').value = expectedYield.toFixed(0);
            document.getElementById('netYield').value = netYield.toFixed(0);
            document.getElementById('wastagePercent').value = wastagePercent + '%';
        }
        
        // Calculate sales revenue
        function calculateSales() {
            const productSelect = document.getElementById('productSelect');
            const quantity = parseFloat(document.getElementById('quantitySold').value) || 0;
            const saleType = document.getElementById('saleType').value;
            
            if (!productSelect.value) return;
            
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const wholesalePrice = parseFloat(selectedOption.getAttribute('data-wholesale')) || 0;
            const retailPrice = parseFloat(selectedOption.getAttribute('data-retail')) || 0;
            
            const unitPrice = saleType === 'Wholesale' ? wholesalePrice : retailPrice;
            const totalRevenue = quantity * unitPrice;
            
            document.getElementById('unitPrice').value = '₦' + unitPrice.toFixed(2);
            document.getElementById('totalSaleRevenue').value = '₦' + totalRevenue.toFixed(2);
        }
        
        // Calculate payroll
        function calculatePayroll() {
            const staffSelect = document.getElementById('staffSelect');
            const daysWorked = parseFloat(document.getElementById('daysWorked').value) || 0;
            
            if (!staffSelect.value) return;
            
            const selectedOption = staffSelect.options[staffSelect.selectedIndex];
            const dailyRate = parseFloat(selectedOption.getAttribute('data-rate')) || 0;
            
            const totalPay = daysWorked * dailyRate;
            
            document.getElementById('dailyRate').value = '₦' + dailyRate.toFixed(2);
            document.getElementById('totalPay').value = '₦' + totalPay.toFixed(2);
        }
        
        // Show/Hide sections
        function showSection(sectionId) {
            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => {
                section.classList.add('hidden');
            });
            
            // Show selected section
            const selectedSection = document.getElementById(sectionId);
            if (selectedSection) {
                selectedSection.classList.remove('hidden');
            }
            
            // Update sidebar active state
            const links = document.querySelectorAll('aside a[href^="#"]');
            links.forEach(link => {
                link.classList.remove('sidebar-active');
                if (link.getAttribute('href') === '#' + sectionId) {
                    link.classList.add('sidebar-active');
                }
            });
        }
    </script>
</body>
</html>