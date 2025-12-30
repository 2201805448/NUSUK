<!DOCTYPE html>
<html>

<head>
    <title>Trips Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        h2 {
            text-align: center;
        }
    </style>
</head>

<body>
    <h2>Trips Report</h2>
    <p>Generated on: <?php echo e(date('Y-m-d H:i')); ?></p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Package</th>
                <th>Capacity</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $trips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($trip->trip_id); ?></td>
                    <td><?php echo e($trip->trip_name); ?></td>
                    <td><?php echo e($trip->status); ?></td>
                    <td><?php echo e($trip->start_date); ?></td>
                    <td><?php echo e($trip->end_date); ?></td>
                    <td><?php echo e($trip->package->package_name ?? 'N/A'); ?></td>
                    <td><?php echo e($trip->capacity); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</body>

</html><?php /**PATH C:\Users\admin\Downloads\NUSUK\resources\views/reports/trips_pdf.blade.php ENDPATH**/ ?>