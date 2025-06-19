<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Number Help - Rocket Production Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'templates/navigation.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-question-circle"></i> คู่มือการใช้ Production Number</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5>Production Number คืออะไร?</h5>
                                        <p>Production Number คือหมายเลขเฉพาะที่ใช้ระบุแต่ละ Production Order โดยแต่ละหมายเลขต้องไม่ซ้ำกัน</p>
                                        
                                        <h5>รูปแบบ Production Number ที่แนะนำ</h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6><i class="fas fa-calendar"></i> รูปแบบตามปี</h6>
                                                        <ul class="list-unstyled">
                                                            <li><code>PO-2025-001</code></li>
                                                            <li><code>PO-2025-002</code></li>
                                                            <li><code>ORDER-2025-001</code></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6><i class="fas fa-rocket"></i> รูปแบบตามโมเดล</h6>
                                                        <ul class="list-unstyled">
                                                            <li><code>M2C-001</code></li>
                                                            <li><code>M3C-001</code></li>
                                                            <li><code>TOP-001</code></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <h5 class="mt-4">ตัวอย่าง Production Number ที่มีอยู่แล้ว</h5>
                                        <div id="existingNumbers">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                        
                                        <h5 class="mt-4">คำแนะนำ Production Number ใหม่</h5>
                                        <div id="suggestions">
                                            <div class="spinner-border text-success" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-warning">
                                            <div class="card-header bg-warning text-dark">
                                                <h6><i class="fas fa-exclamation-triangle"></i> ข้อควรระวัง</h6>
                                            </div>
                                            <div class="card-body">
                                                <ul>
                                                    <li>Production Number ต้องไม่ซ้ำกัน</li>
                                                    <li>ไม่ควรใช้อักขระพิเศษ เช่น @, #, %</li>
                                                    <li>ใช้ตัวอักษรภาษาอังกฤษและตัวเลขเท่านั้น</li>
                                                    <li>ควรใช้รูปแบบที่สม่ำเสมอ</li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <div class="card border-info mt-3">
                                            <div class="card-header bg-info text-white">
                                                <h6><i class="fas fa-lightbulb"></i> เคล็ดลับ</h6>
                                            </div>
                                            <div class="card-body">
                                                <p>เมื่อสร้าง Production Order ใหม่ ระบบจะแสดงคำแนะนำ Production Number ที่ใช้ได้</p>
                                                <a href="create_order.php" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-plus"></i> สร้าง Order ใหม่
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load existing production numbers
        fetch('/rocket-production-management/api/production_number_helper.php?action=suggest')
            .then(r => r.json())
            .then(data => {
                if (data.suggestions) {
                    document.getElementById('suggestions').innerHTML = 
                        data.suggestions.map(s => `<span class="badge bg-success me-2 mb-2">${s}</span>`).join('');
                }
            })
            .catch(() => {
                document.getElementById('suggestions').innerHTML = '<span class="text-muted">ไม่สามารถโหลดคำแนะนำได้</span>';
            });
            
        // Load existing numbers (sample)
        document.getElementById('existingNumbers').innerHTML = `
            <div class="row gx-2">
                <div class="col-auto"><span class="badge bg-secondary">M2C-22B-25</span></div>
                <div class="col-auto"><span class="badge bg-secondary">M3C-TOP-005-001</span></div>
                <div class="col-auto"><span class="badge bg-secondary">PO-2025-001</span></div>
                <div class="col-auto"><span class="badge bg-secondary">TOP-005</span></div>
                <div class="col-auto"><span class="badge bg-secondary">TOP-010</span></div>
            </div>
            <small class="text-muted">นี่คือตัวอย่างหมายเลขที่มีอยู่แล้ว ห้ามใช้ซ้ำ</small>
        `;
    </script>
</body>
</html>
