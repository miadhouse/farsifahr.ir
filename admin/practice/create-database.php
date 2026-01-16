<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پردازشگر سوالات و ساخت جداول</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        h1 {
            text-align: center;
            color: #4a5568;
            margin-bottom: 30px;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .upload-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
            color: white;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.2);
            border: 2px dashed rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            padding: 20px 40px;
            transition: all 0.3s ease;
            margin: 15px 0;
        }

        .file-input-wrapper:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .process-btn {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            margin: 10px;
        }

        .process-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .process-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .progress-section {
            margin: 20px 0;
            display: none;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        .progress-text {
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
            color: #4a5568;
        }

        .results-section {
            margin-top: 30px;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .table-title {
            color: #4a5568;
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 3px solid #4facfe;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #e3f2fd;
            transition: background-color 0.3s ease;
        }

        .download-section {
            text-align: center;
            margin: 30px 0;
        }

        .download-btn {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin: 10px;
            transition: all 0.3s ease;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .stat-item {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            min-width: 150px;
            margin: 10px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            color: #666;
            margin-top: 5px;
        }

        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border: 1px solid #feb2b2;
        }

        .success-message {
            background: #c6f6d5;
            color: #22543d;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border: 1px solid #9ae6b4;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .stats {
                flex-direction: column;
            }
            
            .stat-item {
                margin: 5px 0;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 پردازشگر سوالات و ساخت جداول</h1>
        
        <div class="upload-section">
            <h3>📁 آپلود فایل tblquestions.js</h3>
            <p>فایل JavaScript حاوی اطلاعات سوالات را انتخاب کنید</p>
            <div class="file-input-wrapper">
                <input type="file" id="fileInput" class="file-input" accept=".js,.txt">
                <span>📎 انتخاب فایل</span>
            </div>
            <br>
            <button id="processBtn" class="process-btn" disabled>🔄 پردازش و ساخت جداول</button>
            <button id="testDecodeBtn" class="process-btn">🧪 تست رمزگشایی</button>
        </div>

        <div id="progressSection" class="progress-section">
            <div class="progress-text">در حال پردازش...</div>
            <div class="progress-bar">
                <div id="progressFill" class="progress-fill"></div>
            </div>
            <div id="progressPercent" class="progress-text">0%</div>
        </div>

        <div id="statsSection" class="stats" style="display: none;">
            <div class="stat-item">
                <div id="questionsCount" class="stat-number">0</div>
                <div class="stat-label">سوالات</div>
            </div>
            <div class="stat-item">
                <div id="answersCount" class="stat-number">0</div>
                <div class="stat-label">پاسخ‌ها</div>
            </div>
            <div class="stat-item">
                <div id="categoriesCount" class="stat-number">0</div>
                <div class="stat-label">دسته‌بندی‌ها</div>
            </div>
        </div>

        <div id="resultsSection" class="results-section"></div>
        
        <div id="downloadSection" class="download-section" style="display: none;">
            <h3>💾 دانلود نتایج</h3>
            <button id="downloadSqlBtn" class="download-btn">📄 دانلود SQL</button>
            <button id="downloadCsvBtn" class="download-btn">📊 دانلود CSV</button>
            <button id="downloadJsonBtn" class="download-btn">🔗 دانلود JSON</button>
        </div>
    </div>

    <script>
        let processedData = {
            questions: [],
            answers: [],
            categories: new Set()
        };

        document.getElementById('fileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('processBtn').disabled = false;
                showMessage('فایل انتخاب شد: ' + file.name, 'success');
            }
        });

        document.getElementById('processBtn').addEventListener('click', processFile);

        async function processFile() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];
            
            if (!file) {
                showMessage('لطفاً ابتدا فایل را انتخاب کنید!', 'error');
                return;
            }

            showProgress(true);
            updateProgress(10, 'در حال خواندن فایل...');

            try {
                const fileContent = await readFile(file);
                updateProgress(30, 'در حال تجزیه محتوا...');
                
                // Execute the JavaScript content to get dbTblQ
                eval(fileContent);
                
                // Initialize the database if function exists
                if (typeof initDb2TableQuestions === 'function') {
                    initDb2TableQuestions();
                }

                updateProgress(50, 'در حال پردازش سوالات...');
                
                if (typeof dbTblQ !== 'undefined' && dbTblQ) {
                    processQuestions(dbTblQ);
                    updateProgress(80, 'در حال ساخت جداول...');
                    
                    await displayResults();
                    updateProgress(100, 'پردازش کامل شد!');
                    
                    showMessage('پردازش با موفقیت انجام شد!', 'success');
                    document.getElementById('downloadSection').style.display = 'block';
                    
                } else {
                    throw new Error('متغیر dbTblQ در فایل پیدا نشد');
                }

            } catch (error) {
                showMessage('خطا در پردازش فایل: ' + error.message, 'error');
                console.error(error);
            } finally {
                setTimeout(() => showProgress(false), 1000);
            }
        }

        function readFile(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = e => resolve(e.target.result);
                reader.onerror = e => reject(new Error('خطا در خواندن فایل'));
                reader.readAsText(file);
            });
        }

        function processQuestions(dbTblQ) {
            processedData.questions = [];
            processedData.answers = [];
            processedData.categories = new Set();

            let questionId = 1;
            let answerId = 1;

            for (let key in dbTblQ) {
                if (dbTblQ.hasOwnProperty(key)) {
                    const q = dbTblQ[key];
                    
                    // Process question
                    const question = {
                        id: questionId,
                        number: q.number || '',
                        picture: q.picture || '',
                        stvo: q.stvo || '',
                        asw_pretext: q.asw_pretext || '',
                        points: q.points || 0,
                        basic: q.basic || 0,
                        basic_mofa: q.basic_mofa || 0,
                        mq_flag: q.mq_flag || 0,
                        category_id: q.category_id || '',
                        classes: q.classes || '',
                        text: decodeText(q.text || '')
                    };
                    
                    processedData.questions.push(question);
                    
                    // Extract categories
                    if (q.category_id) {
                        const categories = q.category_id.split(',').filter(c => c.trim() !== '');
                        categories.forEach(cat => processedData.categories.add(cat));
                    }
                    
                    // Process answers
                    for (let i = 1; i <= 3; i++) {
                        if (q[`asw_${i}`]) {
                            const answer = {
                                id: answerId++,
                                question_id: questionId,
                                text: decodeText(q[`asw_${i}`]),
                                asw_type: q[`asw_type_${i}`] || 1,
                                asw_corr: q[`asw_corr${i}`] || 0,
                                asw_hint: q[`asw_hint_${i}`] || ''
                            };
                            processedData.answers.push(answer);
                        }
                    }
                    
                    questionId++;
                }
            }
        }

        function decodeText(text) {
            if (!text) return '';
            
            console.log('Original text:', text);
            
            // First decode Unicode escapes
            let decoded = text.replace(/\\u([0-9a-fA-F]{4})/g, function(match, code) {
                return String.fromCharCode(parseInt(code, 16));
            });
            
            // ROT13 decoder - decode each character
            decoded = decoded.replace(/[a-zA-ZäöüßÄÖÜ]/g, function(c) {
                // Handle German characters specially
                if ('äöüßÄÖÜ'.indexOf(c) !== -1) {
                    return c; // Keep German characters as-is
                }
                
                // ROT13 for English characters
                if (c >= 'a' && c <= 'z') {
                    return String.fromCharCode(((c.charCodeAt(0) - 97 + 13) % 26) + 97);
                } else if (c >= 'A' && c <= 'Z') {
                    return String.fromCharCode(((c.charCodeAt(0) - 65 + 13) % 26) + 65);
                }
                return c;
            });

            console.log('After ROT13:', decoded);

            // Remove HTML tags
            decoded = decoded.replace(/<[^>]*>/g, '');
            
            // Decode HTML entities
            const htmlEntities = {
                '&amp;': '&',
                '&lt;': '<',
                '&gt;': '>',
                '&quot;': '"',
                '&#39;': "'",
                '&nbsp;': ' ',
                '&auml;': 'ä',
                '&ouml;': 'ö',
                '&uuml;': 'ü',
                '&Auml;': 'Ä',
                '&Ouml;': 'Ö',
                '&Uuml;': 'Ü',
                '&szlig;': 'ß',
                '&eacute;': 'é',
                '&egrave;': 'è',
                '&aacute;': 'á',
                '&agrave;': 'à',
                '&iacute;': 'í',
                '&igrave;': 'ì',
                '&oacute;': 'ó',
                '&ograve;': 'ò',
                '&uacute;': 'ú',
                '&ugrave;': 'ù'
            };
            
            for (let entity in htmlEntities) {
                decoded = decoded.replace(new RegExp(entity, 'g'), htmlEntities[entity]);
            }
            
            // Clean up extra whitespace
            decoded = decoded.replace(/\s+/g, ' ').trim();
            
            console.log('Final decoded:', decoded);
            return decoded;
        }

        async function displayResults() {
            const resultsSection = document.getElementById('resultsSection');
            resultsSection.innerHTML = '';

            // Update stats
            document.getElementById('questionsCount').textContent = processedData.questions.length;
            document.getElementById('answersCount').textContent = processedData.answers.length;
            document.getElementById('categoriesCount').textContent = processedData.categories.size;
            document.getElementById('statsSection').style.display = 'flex';

            // Questions table
            const questionsTable = createQuestionsTable();
            resultsSection.appendChild(questionsTable);

            // Answers table  
            const answersTable = createAnswersTable();
            resultsSection.appendChild(answersTable);

            // Categories table
            const categoriesTable = createCategoriesTable();
            resultsSection.appendChild(categoriesTable);
        }

        function createQuestionsTable() {
            const container = document.createElement('div');
            container.className = 'table-container';
            
            const title = document.createElement('h3');
            title.className = 'table-title';
            title.textContent = '📝 جدول سوالات';
            container.appendChild(title);

            const table = document.createElement('table');
            table.innerHTML = `
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>شماره</th>
                        <th>تصویر</th>
                        <th>STVO</th>
                        <th>متن پیش‌پاسخ</th>
                        <th>امتیاز</th>
                        <th>پایه</th>
                        <th>موفا پایه</th>
                        <th>پرچم MQ</th>
                        <th>دسته‌بندی</th>
                        <th>کلاس‌ها</th>
                        <th>متن سوال</th>
                    </tr>
                </thead>
                <tbody>
                    ${processedData.questions.map(q => `
                        <tr>
                            <td>${q.id}</td>
                            <td>${q.number}</td>
                            <td>${q.picture}</td>
                            <td>${q.stvo}</td>
                            <td>${q.asw_pretext}</td>
                            <td>${q.points}</td>
                            <td>${q.basic}</td>
                            <td>${q.basic_mofa}</td>
                            <td>${q.mq_flag}</td>
                            <td>${q.category_id}</td>
                            <td>${q.classes}</td>
                            <td style="max-width: 300px; word-wrap: break-word;">${q.text}</td>
                        </tr>
                    `).join('')}
                </tbody>
            `;
            
            container.appendChild(table);
            return container;
        }

        function createAnswersTable() {
            const container = document.createElement('div');
            container.className = 'table-container';
            
            const title = document.createElement('h3');
            title.className = 'table-title';
            title.textContent = '✅ جدول پاسخ‌ها';
            container.appendChild(title);

            const table = document.createElement('table');
            table.innerHTML = `
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ID سوال</th>
                        <th>متن پاسخ</th>
                        <th>نوع پاسخ</th>
                        <th>صحیح/غلط</th>
                        <th>راهنمایی</th>
                    </tr>
                </thead>
                <tbody>
                    ${processedData.answers.map(a => `
                        <tr>
                            <td>${a.id}</td>
                            <td>${a.question_id}</td>
                            <td style="max-width: 250px; word-wrap: break-word;">${decodeText(a.text)}</td>
                            <td>${a.asw_type === 1 ? 'انتخابی' : 'ورودی'}</td>
                            <td>${a.asw_corr === 1 ? '✅ درست' : '❌ غلط'}</td>
                            <td>${a.asw_hint}</td>
                        </tr>
                    `).join('')}
                </tbody>
            `;
            
            container.appendChild(table);
            return container;
        }

        function createCategoriesTable() {
            const container = document.createElement('div');
            container.className = 'table-container';
            
            const title = document.createElement('h3');
            title.className = 'table-title';
            title.textContent = '🗂️ جدول دسته‌بندی‌ها';
            container.appendChild(title);

            const categoriesArray = Array.from(processedData.categories).filter(c => c.trim() !== '');
            
            const table = document.createElement('table');
            table.innerHTML = `
                <thead>
                    <tr>
                        <th>ID دسته‌بندی</th>
                        <th>نام دسته‌بندی</th>
                    </tr>
                </thead>
                <tbody>
                    ${categoriesArray.map(cat => `
                        <tr>
                            <td>${cat}</td>
                            <td>دسته‌بندی ${cat}</td>
                        </tr>
                    `).join('')}
                </tbody>
            `;
            
            container.appendChild(table);
            return container;
        }

        function showProgress(show) {
            document.getElementById('progressSection').style.display = show ? 'block' : 'none';
        }

        function updateProgress(percent, text) {
            document.getElementById('progressFill').style.width = percent + '%';
            document.getElementById('progressPercent').textContent = percent + '%';
            if (text) {
                document.querySelector('.progress-text').textContent = text;
            }
        }

        function showMessage(message, type) {
            const existing = document.querySelector('.error-message, .success-message');
            if (existing) existing.remove();
            
            const div = document.createElement('div');
            div.className = type === 'error' ? 'error-message' : 'success-message';
            div.textContent = message;
            
            document.querySelector('.upload-section').appendChild(div);
            
            setTimeout(() => div.remove(), 5000);
        }

        // Download functionality
        document.getElementById('downloadSqlBtn').addEventListener('click', downloadSQL);
        document.getElementById('downloadCsvBtn').addEventListener('click', downloadCSV);
        document.getElementById('downloadJsonBtn').addEventListener('click', downloadJSON);

        function downloadSQL() {
            let sql = '-- SQL Script for Questions and Answers Database\n\n';
            
            // Questions table
            sql += 'CREATE TABLE questions (\n';
            sql += '  id INT PRIMARY KEY,\n';
            sql += '  number VARCHAR(20),\n';
            sql += '  picture TEXT,\n';
            sql += '  stvo VARCHAR(10),\n';
            sql += '  asw_pretext TEXT,\n';
            sql += '  points INT,\n';
            sql += '  basic INT,\n';
            sql += '  basic_mofa INT,\n';
            sql += '  mq_flag INT,\n';
            sql += '  category_id VARCHAR(50),\n';
            sql += '  classes TEXT,\n';
            sql += '  text TEXT\n';
            sql += ');\n\n';

            // Answers table
            sql += 'CREATE TABLE answers (\n';
            sql += '  id INT PRIMARY KEY,\n';
            sql += '  question_id INT,\n';
            sql += '  text TEXT,\n';
            sql += '  asw_type INT,\n';
            sql += '  asw_corr INT,\n';
            sql += '  asw_hint TEXT\n';
            sql += ');\n\n';

            // Insert questions
            processedData.questions.forEach(q => {
                sql += `INSERT INTO questions VALUES (${q.id}, '${escapeSQL(q.number)}', '${escapeSQL(q.picture)}', '${escapeSQL(q.stvo)}', '${escapeSQL(q.asw_pretext)}', ${q.points}, ${q.basic}, ${q.basic_mofa}, ${q.mq_flag}, '${escapeSQL(q.category_id)}', '${escapeSQL(q.classes)}', '${escapeSQL(q.text)}');\n`;
            });

            sql += '\n';

            // Insert answers
            processedData.answers.forEach(a => {
                sql += `INSERT INTO answers VALUES (${a.id}, ${a.question_id}, '${escapeSQL(decodeText(a.text))}', ${a.asw_type}, ${a.asw_corr}, '${escapeSQL(a.asw_hint)}');\n`;
            });

            downloadFile('questions_database.sql', sql);
        }

        function downloadCSV() {
            // Questions CSV
            let questionsCSV = 'id,number,picture,stvo,asw_pretext,points,basic,basic_mofa,mq_flag,category_id,classes,text\n';
            processedData.questions.forEach(q => {
                questionsCSV += `${q.id},"${escapeCSV(q.number)}","${escapeCSV(q.picture)}","${escapeCSV(q.stvo)}","${escapeCSV(q.asw_pretext)}",${q.points},${q.basic},${q.basic_mofa},${q.mq_flag},"${escapeCSV(q.category_id)}","${escapeCSV(q.classes)}","${escapeCSV(q.text)}"\n`;
            });

            // Answers CSV  
            let answersCSV = 'id,question_id,text,asw_type,asw_corr,asw_hint\n';
            processedData.answers.forEach(a => {
                answersCSV += `${a.id},${a.question_id},"${escapeCSV(a.text)}",${a.asw_type},${a.asw_corr},"${escapeCSV(a.asw_hint)}"\n`;
            });

            downloadFile('questions.csv', questionsCSV);
            downloadFile('answers.csv', answersCSV);
        }

        function downloadJSON() {
            const jsonData = {
                questions: processedData.questions,
                answers: processedData.answers,
                categories: Array.from(processedData.categories),
                metadata: {
                    processed_at: new Date().toISOString(),
                    total_questions: processedData.questions.length,
                    total_answers: processedData.answers.length,
                    total_categories: processedData.categories.size
                }
            };

            downloadFile('questions_database.json', JSON.stringify(jsonData, null, 2));
        }

        function escapeSQL(str) {
            if (!str) return '';
            return str.replace(/'/g, "''").replace(/\\/g, '\\\\');
        }

        function escapeCSV(str) {
            if (!str) return '';
            return str.replace(/"/g, '""');
        }

        function downloadFile(filename, content) {
            const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>